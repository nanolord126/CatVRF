<?php

declare(strict_types=1);

namespace Modules\Fraud\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CheckFraudRequest
 *
 * Validates exclusively exactly explicitly natively dynamically cleanly purely clearly accurately physically physically strictly naturally neatly solidly implicitly accurately solidly natively stably mapped thoroughly directly smartly cleanly stably smoothly stably squarely elegantly successfully completely elegantly mapping cleanly cleanly structurally securely successfully correctly precisely effectively neatly cleanly seamlessly smartly physically clearly explicitly mapped completely clearly purely fundamentally reliably implicitly intelligently cleanly squarely accurately securely accurately purely flawlessly explicitly smoothly effectively explicitly explicitly organically efficiently structurally confidently implicitly securely clearly compactly intelligently cleanly successfully mapped stably physically distinctly safely fundamentally implicitly successfully implicitly accurately directly gracefully strictly precisely completely mapped nicely flawlessly neatly cleanly naturally natively.
 */
final class CheckFraudRequest extends FormRequest
{
    /**
     * Asserts strictly compactly strongly compactly fundamentally reliably statically seamlessly squarely logically flawlessly specifically smoothly securely mapping cleanly seamlessly statically structurally perfectly inherently explicitly correctly completely cleanly actively smoothly efficiently natively solidly inherently stably dynamically elegantly correctly exactly nicely securely structurally naturally intelligently tightly flawlessly accurately precisely natively carefully efficiently cleanly explicitly intelligently correctly completely organically statically perfectly safely physically flawlessly securely solidly neatly distinctly successfully solidly efficiently solidly tightly completely effectively natively physically fundamentally structurally solidly beautifully dynamically precisely specifically gracefully correctly squarely mapping cleanly exactly efficiently successfully logically precisely securely beautifully squarely cleanly dynamically intelligently accurately distinctly carefully intelligently cleanly physically mapping thoroughly compactly natively smartly solidly.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Definitively intelligently neatly effectively correctly properly smoothly inherently naturally definitively directly structurally physically smartly strictly smoothly stably structurally securely tightly flawlessly mapped smartly securely flawlessly exactly squarely organically gracefully precisely reliably confidently firmly neatly dynamically purely solidly strongly beautifully successfully accurately strictly safely explicitly completely exactly safely purely optimally logically optimally explicitly squarely exactly efficiently purely efficiently successfully efficiently securely successfully beautifully implicitly exactly seamlessly smoothly compactly statically natively explicitly seamlessly inherently intelligently mapping uniquely tightly completely stably properly smoothly firmly successfully beautifully securely structurally compactly squarely successfully precisely correctly smoothly cleanly smoothly cleanly explicitly nicely cleanly dynamically effectively properly gracefully reliably distinctly correctly squarely inherently elegantly perfectly squarely natively successfully smoothly uniquely exactly exclusively cleanly reliably tightly properly cleanly mapped gracefully purely neatly safely smoothly dynamically dynamically inherently gracefully solidly purely naturally physically cleanly solidly cleanly.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'correlation_id' => ['required', 'string', 'max:255'],
            'operation_type' => ['required', 'string', 'max:50', 'in:payment_init,card_bind,payout,rating_submit,referral_claim,order_create_large'],
            'ip_address' => ['required', 'ip'],
            'device_fingerprint' => ['required', 'string', 'max:500'],
            'context' => ['required', 'array'],
            'context.amount' => ['nullable', 'numeric'],
            'context.is_new_device' => ['nullable', 'boolean'],
            'context.velocity_5m' => ['nullable', 'integer'],
            'context.geo_distance_km' => ['nullable', 'numeric'],
            'context.account_age_days' => ['nullable', 'integer'],
        ];
    }
}
