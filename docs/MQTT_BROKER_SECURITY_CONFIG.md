# MQTT Broker Security Configuration for Restaurant IoT

**Version:** 1.0  
**Date:** 2026-04-23  
**Purpose:** Secure MQTT broker configuration with mTLS, ACL, and tenant isolation for CatVRF Restaurant IoT

## Overview

This guide provides production-ready EMQX (or equivalent MQTT broker) configuration for secure IoT device communication in restaurant environments.

## Security Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Internet / Cloud                        │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│              Load Balancer / Reverse Proxy                  │
│              (TLS Termination - Optional)                   │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                   MQTT Broker (EMQX)                        │
│  ┌──────────────────────────────────────────────────────┐  │
│  │              mTLS Authentication                      │  │
│  │  - Client Certificate Validation                      │  │
│  │  - Certificate Revocation Check (CRL/OCSP)            │  │
│  │  - Device Attestation                                 │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │              Access Control Lists (ACL)               │  │
│  │  - Tenant Isolation                                   │  │
│  │  - Device-Topic Mapping                              │  │
│  │  - Rate Limiting                                      │  │
│  └──────────────────────────────────────────────────────┘  │
└────────────────────────┬────────────────────────────────────┘
                         │
         ┌───────────────┼───────────────┐
         ▼               ▼               ▼
    ┌─────────┐    ┌─────────┐    ┌─────────┐
    │ Tenant 1│    │ Tenant 2│    │ Tenant N│
    │ Devices │    │ Devices │    │ Devices │
    └─────────┘    └─────────┘    └─────────┘
```

## 1. EMQX Configuration (emqx.conf)

### 1.1 Basic TLS Configuration

```hocon
## EMQX Configuration for CatVRF Restaurant IoT

## Listener Configuration
listeners.ssl.default {
  bind = "0.0.0.0:8883"
  max_connections = 102400
  max_conn_rate = 1000
  handshake_timeout = 15s

  ## TLS Configuration
  ssl_options {
    keyfile = "/etc/emqx/certs/server.key"
    certfile = "/etc/emqx/certs/server.crt"
    cacertfile = "/etc/emqx/certs/ca.crt"
    
    ## TLS 1.3 Only
    versions = ["tlsv1.3"]
    
    ## Mutual TLS
    verify = verify_peer
    fail_if_no_peer_cert = true
    
    ## Certificate Validation
    depth = 5
    custom_verify {
      enabled = true
      verify_fun = emqx_tls_psk:verify
    }
  }
}

## WebSocket Secure Listener
listeners.wss.default {
  bind = "0.0.0.0:8084"
  max_connections = 102400
  mqtt_path = "/mqtt"
  ssl_options {
    keyfile = "/etc/emqx/certs/server.key"
    certfile = "/etc/emqx/certs/server.crt"
    cacertfile = "/etc/emqx/certs/ca.crt"
    versions = ["tlsv1.3"]
    verify = verify_peer
    fail_if_no_peer_cert = true
  }
}
```

### 1.2 Authentication with mTLS

```hocon
## Authentication Configuration
authentication {
  backend = built_in_database
  mechanism = password_based
  user_id_type = username
  password_hash_algorithm {name = sha256}
  
  ## Enable mTLS Authentication
  ssl {
    enable = true
    depth = 5
    verify = verify_peer
    fail_if_no_peer_cert = true
  }
}

## Client Certificate Authentication
authentication.$1 {
  mechanism = client_cert
  backend = built_in_database
  enable = true
  acl_nomatch = deny
}
```

### 1.3 ACL Configuration for Tenant Isolation

```hocon
## Access Control Lists
authorization {
  sources = [
    {
      type = built_in_database
      enable = true
    }
  ]
  no_match = deny
  deny_action = disconnect
}

## Default ACL Rules (Database)
## Format: clientid, username, topic, action, result
##
## Examples:
## catvrf_t1_device_001, t1_user, catcrm/1/telemetry/#, publish, allow
## catvrf_t1_device_001, t1_user, catcrm/1/commands/+, subscribe, allow
## catvrf_t2_device_001, t2_user, catcrm/1/telemetry/#, publish, deny
```

### 1.4 Rate Limiting

```hocon
## Rate Limiting Configuration
limiter {
  client {
    max_connections = 100
    max_subscriptions = 20
    max_inflight = 32
  }
  
  connection {
    max_connections = 1000
    rate_limit = 100
  }
  
  messages {
    rate_limit = 1000
    burst_limit = 2000
  }
}
```

## 2. Certificate Management

### 2.1 CA Certificate Setup

```bash
# Create CA directory
mkdir -p /etc/emqx/certs
cd /etc/emqx/certs

# Generate CA Private Key
openssl genrsa -out ca.key 4096

# Generate CA Certificate
openssl req -new -x509 -days 3650 -key ca.key -out ca.crt \
  -subj "/C=RU/ST=Moscow/L=Moscow/O=CatVRF/OU=IoT/CN=CatVRF IoT CA"

# Set permissions
chmod 600 ca.key
chmod 644 ca.crt
```

### 2.2 Server Certificate

```bash
# Generate Server Private Key
openssl genrsa -out server.key 4096

# Generate CSR
openssl req -new -key server.key -out server.csr \
  -subj "/C=RU/ST=Moscow/L=Moscow/O=CatVRF/OU=IoT/CN=iot.catvrf.ru"

# Sign with CA
openssl x509 -req -days 365 -in server.csr -CA ca.crt -CAkey ca.key \
  -CAcreateserial -out server.crt

# Set permissions
chmod 600 server.key
chmod 644 server.crt
```

### 2.3 Device Certificate Generation Script

```bash
#!/bin/bash
# generate_device_cert.sh

DEVICE_ID=$1
TENANT_ID=$2
OUTPUT_DIR=$3

if [ -z "$DEVICE_ID" ] || [ -z "$TENANT_ID" ]; then
  echo "Usage: $0 <device_id> <tenant_id> [output_dir]"
  exit 1
fi

OUTPUT_DIR=${OUTPUT_DIR:-./certs}
mkdir -p $OUTPUT_DIR

# Generate Device Private Key
openssl genrsa -out $OUTPUT_DIR/${DEVICE_ID}.key 4096

# Generate CSR with device identifier
openssl req -new -key $OUTPUT_DIR/${DEVICE_ID}.key -out $OUTPUT_DIR/${DEVICE_ID}.csr \
  -subj "/C=RU/ST=Moscow/L=Moscow/O=CatVRF/OU=IoT/CN=${DEVICE_ID}"

# Sign with CA
openssl x509 -req -days 90 -in $OUTPUT_DIR/${DEVICE_ID}.csr \
  -CA ca.crt -CAkey ca.key -CAcreateserial \
  -out $OUTPUT_DIR/${DEVICE_ID}.crt \
  -extfile <(echo "subjectAltName=DNS:${DEVICE_ID},URI:urn:catvrf:iot:tenant:${TENANT_ID}:device:${DEVICE_ID}")

# Extract fingerprint
CERT_FINGERPRINT=$(openssl x509 -in $OUTPUT_DIR/${DEVICE_ID}.crt -noout -fingerprint -sha256 | cut -d= -f2 | tr -d :)

echo "Certificate generated:"
echo "  Device ID: $DEVICE_ID"
echo "  Tenant ID: $TENANT_ID"
echo "  Fingerprint: $CERT_FINGERPRINT"
echo "  Files: $OUTPUT_DIR/${DEVICE_ID}.{key,crt}"

# Set permissions
chmod 600 $OUTPUT_DIR/${DEVICE_ID}.key
chmod 644 $OUTPUT_DIR/${DEVICE_ID}.crt
```

### 2.4 Certificate Revocation List (CRL)

```bash
# Create CRL index file
touch /etc/emqx/certs/index.txt
echo 1000 > /etc/emqx/certs/crlnumber

# Revoke a certificate
openssl ca -revoke certs/${DEVICE_ID}.crt \
  -cert ca.crt -keyfile ca.key \
  -crl_reason keyCompromise

# Generate CRL
openssl ca -gencrl -out /etc/emqx/certs/crl.pem \
  -cert ca.crt -keyfile ca.key
```

## 3. Database ACL Configuration

### 3.1 MySQL/PostgreSQL Schema

```sql
-- EMQX ACL Tables
CREATE TABLE mqtt_user (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_superuser BOOLEAN DEFAULT FALSE,
  tenant_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB;

CREATE TABLE mqtt_acl (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  clientid VARCHAR(100) NOT NULL,
  topic VARCHAR(255) NOT NULL,
  action ENUM('publish', 'subscribe') NOT NULL,
  permission ENUM('allow', 'deny') NOT NULL DEFAULT 'allow',
  tenant_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_username (username),
  INDEX idx_clientid (clientid),
  INDEX idx_topic (topic),
  INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB;

-- Insert device ACL rule
INSERT INTO mqtt_acl (username, clientid, topic, action, permission, tenant_id)
VALUES (
  'tenant_1_user',
  'catvrf_t1_device_001',
  'catcrm/1/telemetry/#',
  'publish',
  'allow',
  1
);

INSERT INTO mqtt_acl (username, clientid, topic, action, permission, tenant_id)
VALUES (
  'tenant_1_user',
  'catvrf_t1_device_001',
  'catcrm/1/commands/+',
  'subscribe',
  'allow',
  1
);
```

### 3.2 Topic Structure

```
catcrm/{tenant_id}/telemetry/{device_id}      # Device telemetry (publish)
catcrm/{tenant_id}/commands/{device_id}        # Commands to device (subscribe)
catcrm/{tenant_id}/status/{device_id}          # Device status (publish/subscribe)
catcrm/{tenant_id}/alerts/{device_id}          # Device alerts (publish)
catcrm/{tenant_id}/discovery                   # Device discovery (subscribe)
```

## 4. Network Isolation

### 4.1 Firewall Rules (iptables)

```bash
# Allow MQTT broker from IoT VLAN only
iptables -A INPUT -p tcp --dport 8883 -s 10.10.20.0/24 -j ACCEPT
iptables -A INPUT -p tcp --dport 8084 -s 10.10.20.0/24 -j ACCEPT

# Allow from application servers
iptables -A INPUT -p tcp --dport 8883 -s 10.10.10.0/24 -j ACCEPT

# Deny all other MQTT traffic
iptables -A INPUT -p tcp --dport 8883 -j DROP
iptables -A INPUT -p tcp --dport 8084 -j DROP
```

### 4.2 VLAN Configuration Example

```bash
# IoT VLAN (VLAN 20)
vlan 20
 name IoT_Devices
 untagged 1-20
 ip address 10.10.20.1/24

# Application VLAN (VLAN 10)
vlan 10
 name Application_Servers
 untagged 21-40
 ip address 10.10.10.1/24
```

## 5. Monitoring and Logging

### 5.1 EMQX Logging Configuration

```hocon
## Logging Configuration
log {
  console_handler {
    enable = true
    level = warning
  }
  
  file_handler {
    enable = true
    level = info
    path = "/var/log/emqx/emqx.log"
    rotation_size = 50MB
    rotation_count = 10
  }
  
  ## Security Event Logging
  security_log {
    enable = true
    path = "/var/log/emqx/security.log"
    level = warning
  }
}
```

### 5.2 Prometheus Metrics

```hocon
## Prometheus Metrics
prometheus {
  push_gateway_server = "http://prometheus:9091"
  interval = 15s
  
  headers {
    Authorization = "Bearer ${PROMETHEUS_TOKEN}"
  }
}
```

## 6. Security Checklist

- [ ] TLS 1.3 only (disable TLS 1.0, 1.1, 1.2)
- [ ] Mutual TLS (mTLS) enabled for all connections
- [ ] Certificate pinning implemented
- [ ] CRL (Certificate Revocation List) configured and updated regularly
- [ ] ACL rules enforce tenant isolation
- [ ] Rate limiting configured per device and per tenant
- [ ] Network isolation (VLANs) implemented
- [ ] Firewall rules restrict MQTT access
- [ ] Security events logged to dedicated file
- [ ] Prometheus metrics enabled for monitoring
- [ ] Regular certificate rotation (30 days)
- [ ] Device attestation on first connection
- [ ] IP whitelist/blacklist configured

## 7. Integration with CatVRF

### 7.1 Environment Variables (.env)

```env
# MQTT Broker Security
MQTT_HOST=iot.catvrf.ru
MQTT_PORT=8883
MQTT_USE_TLS=true
MQTT_USERNAME=catvrf_iot_hub
MQTT_PASSWORD=your_secure_password

# TLS Certificates
IOT_TLS_ENABLED=true
IOT_TLS_VERSION=TLSv1.3
IOT_TLS_VERIFY_PEER=true
IOT_MTLS_ENABLED=true
IOT_TLS_CA_FILE=/etc/catvrf/certs/ca.crt
IOT_TLS_CERT_FILE=/etc/catvrf/certs/client.crt
IOT_TLS_KEY_FILE=/etc/catvrf/certs/client.key

# Security Settings
IOT_RATE_LIMIT_ENABLED=true
IOT_RATE_LIMIT_DEFAULT=60
IOT_ANOMALY_DETECTION_ENABLED=true
IOT_AUTO_QUARANTINE_ENABLED=true
IOT_REPLAY_PROTECTION_ENABLED=true
```

### 7.2 Certificate Registration Flow

```php
// When a new device is registered:
$certificate = DeviceCertificate::create(
    iotDeviceId: $device->id,
    tenantId: $tenant->id,
    certificateFingerprint: $fingerprint,
    publicKey: $publicKeyPem,
    certificatePem: $fullCertificate,
    serialNumber: $serialNumber,
    issuer: 'CatVRF IoT CA',
    issuedAt: CarbonImmutable::parse($issuedAt),
    expiresAt: CarbonImmutable::parse($expiresAt),
    deviceMacAddress: $macAddress,
    deviceSerial: $hardwareSerial,
);

$certificateRepository->save($certificate);

// Add ACL rule in MQTT broker
mqttAclRepository->create([
    'username' => "tenant_{$tenant->id}_user",
    'clientid' => $device->deviceIdentifier,
    'topic' => "catcrm/{$tenant->id}/telemetry/{$device->id}",
    'action' => 'publish',
    'permission' => 'allow',
    'tenant_id' => $tenant->id,
]);
```

## 8. Troubleshooting

### 8.1 Common Issues

**Issue: Certificate verification failed**
```
Solution: Check certificate chain, ensure CA certificate is trusted,
verify certificate hasn't expired or been revoked.
```

**Issue: ACL deny all traffic**
```
Solution: Verify ACL rules in database, check username/clientid matching,
ensure topic patterns use wildcards correctly.
```

**Issue: High CPU usage**
```
Solution: Check rate limiting configuration, monitor connection count,
consider horizontal scaling of MQTT broker.
```

## 9. References

- [EMQX Documentation](https://www.emqx.io/docs/en/latest/)
- [MQTT 5.0 Specification](http://mqtt.org/mqtt-specification/)
- [RFC 5246 - TLS 1.2](https://tools.ietf.org/html/rfc5246)
- [RFC 8446 - TLS 1.3](https://tools.ietf.org/html/rfc8446)
- [ФСТЭК №21 - ИСПДн Requirements](https://fstec.ru/)

---

**Document Owner:** CatVRF Security Team  
**Last Updated:** 2026-04-23  
**Classification:** Internal Use Only
