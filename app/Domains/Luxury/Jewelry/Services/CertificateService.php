<?php declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class CertificateService
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Выпуск цифрового сертификата на ювелирное изделие.
         */
        public function issueCertificate(int $itemId, array $data, string $correlationId = ""): JewelryCertificate
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $item = JewelryItem::findOrFail($itemId);

            return $this->db->transaction(function () use ($item, $data, $correlationId) {
                // 1. Проверка на дублирование сертификата
                if (JewelryCertificate::where("item_id", $item->id)->where("status", "active")->exists()) {
                    throw new \RuntimeException("Item already has an active certificate.", 409);
                }

                // 2. ПОД/ФТ проверка (ФЗ-115) при высокой стоимости
                if ($item->price_kopecks > 60000000) { // 600к руб
                    $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                }

                $certificate = JewelryCertificate::create([
                    "uuid" => (string) Str::uuid(),
                    "tenant_id" => $item->tenant_id,
                    "item_id" => $item->id,
                    "certificate_number" => "CERT-" . strtoupper(Str::random(10)),
                    "issuer_org" => $data["issuer"] ?? "GIA Russia Simulation",
                    "metal_purity" => $data["metal_purity"],
                    "stone_characteristics" => $data["stones"] ?? [],
                    "status" => "active",
                    "issued_at" => now(),
                    "correlation_id" => $correlationId
                ]);

                $this->logger->info("Jewelry: certificate issued", [
                    "cert_uuid" => $certificate->uuid,
                    "item_id" => $item->id
                ]);

                return $certificate;
            });
        }

        /**
         * Валидация сертификата при перепродаже или возврате.
         */
        public function validateCertificate(string $certNumber): array
        {
            $cert = JewelryCertificate::with("item")
                ->where("certificate_number", $certNumber)
                ->first();

            if (!$cert) {
                return ["is_valid" => false, "reason" => "not_found"];
            }

            $isValid = $cert->status === "active";

            return [
                "is_valid" => $isValid,
                "details" => $isValid ? [
                    "item_name" => $cert->item->name,
                    "purity" => $cert->metal_purity,
                    "issued_by" => $cert->issuer_org
                ] : null
            ];
        }

        /**
         * Аннулирование сертификата (при утере или переплавке).
         */
        public function revokeCertificate(int $certId, string $reason, string $correlationId = ""): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $cert = JewelryCertificate::findOrFail($certId);

            $this->db->transaction(function () use ($cert, $reason, $correlationId) {
                $cert->update([
                    "status" => "revoked",
                    "revocation_reason" => $reason,
                    "revoked_at" => now()
                ]);

                $this->logger->warning("Jewelry: certificate revoked", [
                    "cert_id" => $cert->id,
                    "reason" => $reason,
                    "correlation_id" => $correlationId
                ]);
            });
        }
}
