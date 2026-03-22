declare(strict_types=1);

/**

* GitHub Actions Secrets Setup Guide
*
* Configure required secrets for CI/CD pipelines
 */

# GitHub Secrets Configuration Guide

## Overview

This guide explains how to set up GitHub repository secrets for the CI/CD pipelines.

## Steps to Add Secrets

### 1. Access Repository Settings

1. Go to your GitHub repository
2. Click **Settings** (top right)
3. Select **Secrets and variables** → **Actions**

### 2. Create New Secret

1. Click **New repository secret**
2. Enter secret name (see below)
3. Paste value
4. Click **Add secret**

---

## Required Secrets

### Staging Environment

#### STAGING_HOST
* **Name**: `STAGING_HOST`
* **Value**: `staging.catvrf.local` or your staging domain
* **Purpose**: SSH connection to staging server

#### STAGING_USER
* **Name**: `STAGING_USER`
* **Value**: `deploy` (or deployment user)
* **Purpose**: SSH user for staging server

#### STAGING_SSH_KEY
* **Name**: `STAGING_SSH_KEY`
* **Value**: Private SSH key (see SSH Key Setup below)
* **Purpose**: SSH authentication for staging

### Production Environment

#### PRODUCTION_HOST
* **Name**: `PRODUCTION_HOST`
* **Value**: `app.catvrf.com` or your production domain
* **Purpose**: SSH connection to production server

#### PRODUCTION_USER
* **Name**: `PRODUCTION_USER`
* **Value**: `deploy` (or deployment user)
* **Purpose**: SSH user for production server

#### PRODUCTION_SSH_KEY
* **Name**: `PRODUCTION_SSH_KEY`
* **Value**: Private SSH key (see SSH Key Setup below)
* **Purpose**: SSH authentication for production

#### DATABASE_URL
* **Name**: `DATABASE_URL`
* **Value**: `postgresql://user:password@host:5432/database`
* **Purpose**: Database connection for backups

### Notifications

#### SLACK_WEBHOOK
* **Name**: `SLACK_WEBHOOK`
* **Value**: `https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX`
* **Purpose**: Send deployment notifications to Slack

---

## SSH Key Setup

### Generate SSH Key (if needed)

```bash
# Generate new key pair
ssh-keygen -t rsa -b 4096 -f catvrf-deploy -C "catvrf-ci@github.com"

# This creates two files:
# - catvrf-deploy (private key)
# - catvrf-deploy.pub (public key)
```

### Install Public Key on Server

```bash
# On your deployment server, add public key to authorized_keys
cat catvrf-deploy.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys

# Test connection
ssh -i catvrf-deploy deploy@staging.catvrf.local
```

### Add Private Key to GitHub Secrets

```bash
# Copy private key content
cat catvrf-deploy | pbcopy  # macOS
cat catvrf-deploy | xclip   # Linux
cat catvrf-deploy           # Windows - copy manually

# Paste into STAGING_SSH_KEY secret on GitHub
```

### Verify Key Permissions

```bash
# On server
ls -la ~/.ssh/authorized_keys
# Should be: -rw------- (600)

# Local key
ls -la catvrf-deploy
# Should be: -rw------- (600)
```

---

## Slack Integration Setup

### Create Incoming Webhook

1. Go to [Slack API](https://api.slack.com/apps)
2. Click **Create New App** → **From scratch**
3. Enter app name: `CatVRF Deployments`
4. Select workspace
5. Go to **Incoming Webhooks** (left menu)
6. Click **Add New Webhook to Workspace**
7. Select channel: `#deployments`
8. Copy webhook URL
9. Paste into `SLACK_WEBHOOK` secret

### Message Format

Deployment notifications include:
* Status (success/failure)
* Environment (staging/production)
* Branch/version deployed
* Commit hash
* Deploying user

---

## Database Backup Setup

### PostgreSQL Backup User

```bash
# Create backup role (on production server)
sudo -u postgres psql << EOF
CREATE ROLE backup_user WITH LOGIN PASSWORD 'secure_password_here';
ALTER ROLE backup_user CREATEDB;
GRANT CONNECT ON DATABASE catvrf TO backup_user;
EOF
```

### Set DATABASE_URL

```bash
# Format:
postgresql://user:password@host:5432/database

# Example:
postgresql://backup_user:secure_password_here@localhost:5432/catvrf

# For production:
postgresql://backup_user:secure_password_here@app.catvrf.com:5432/catvrf
```

---

## Environment-Specific Settings

### GitHub Environments

For additional protection, create environments:

1. Go to **Settings** → **Environments**
2. Click **New environment**
3. Name: `staging` or `production`
4. Add deployment branch restrictions
5. Add required reviewers (for production)

### Production Protection Rules

For production deployments:

1. Create environment: `production`
2. Enable **Require reviewers before deploying**
3. Add team members as reviewers
4. Enable **Dismiss stale pull request approvals**

---

## Verification

### Test Secrets Configuration

```bash
# In GitHub Actions workflow, add debugging:
- name: Verify secrets
  run: |
    echo "STAGING_HOST is set: ${{ secrets.STAGING_HOST != '' }}"
    echo "STAGING_USER is set: ${{ secrets.STAGING_USER != '' }}"
    echo "STAGING_SSH_KEY is set: ${{ secrets.STAGING_SSH_KEY != '' }}"
    # DO NOT echo actual values!
```

### Test SSH Connection

```bash
# Create test action in workflow
- name: Test SSH connection
  run: |
    mkdir -p ~/.ssh
    echo "${{ secrets.STAGING_SSH_KEY }}" > ~/.ssh/id_rsa
    chmod 600 ~/.ssh/id_rsa
    ssh -o StrictHostKeyChecking=no deploy@${{ secrets.STAGING_HOST }} "echo 'SSH works!'"
```

---

## Security Best Practices

### ✅ DO
* Use strong SSH keys (4096-bit RSA)
* Rotate keys every 90 days
* Use separate keys for staging/production
* Restrict SSH key permissions (600)
* Store SSH keys securely locally
* Use RBAC for GitHub access

### ❌ DON'T
* Store secrets in code or `.env` files
* Use same SSH key for all environments
* Share private keys via email/chat
* Commit `.env` or key files to GitHub
* Use weak passwords for backup user
* Log or echo secret values

---

## Troubleshooting

### SSH Connection Fails

```bash
# Check GitHub Actions logs for:
# - "Permission denied (publickey)"
# - "Could not resolve hostname"

# Common fixes:
1. Verify STAGING_SSH_KEY is complete (no truncation)
2. Check authorized_keys on server
3. Verify firewall allows SSH (port 22)
4. Test SSH locally:
   ssh -i key_file deploy@staging.host
```

### Slack Notification Not Sent

```bash
# Check:
1. SLACK_WEBHOOK URL is complete
2. Webhook not revoked (test in Slack settings)
3. Channel still exists and bot has access
4. Try manual test:
   curl -X POST -H 'Content-type: application/json' \
     --data '{"text":"Test"}' \
     $SLACK_WEBHOOK
```

### Database Backup Fails

```bash
# Check:
1. DATABASE_URL format is correct
2. User has backup permissions
3. Database is accessible from GitHub Actions
4. Network/firewall allows connection
```

---

## Maintenance

### Regular Updates

* [ ] Rotate SSH keys quarterly
* [ ] Review secret access logs monthly
* [ ] Update staging/production servers
* [ ] Test backup restoration quarterly
* [ ] Verify Slack webhook availability

### Audit Trail

All secret access is logged in GitHub:

1. Go to **Settings** → **Audit log**
2. Filter by type: "secret"
3. Review recent access

---

## Quick Reference

| Secret | Environment | Required | Format |
|--------|-------------|----------|--------|
| STAGING_HOST | CI/CD | Yes | domain/IP |
| STAGING_USER | CI/CD | Yes | username |
| STAGING_SSH_KEY | CI/CD | Yes | private key |
| PRODUCTION_HOST | CI/CD | Yes | domain/IP |
| PRODUCTION_USER | CI/CD | Yes | username |
| PRODUCTION_SSH_KEY | CI/CD | Yes | private key |
| DATABASE_URL | CI/CD | Yes | postgresql://... |
| SLACK_WEBHOOK | CI/CD | No | <https://hooks>... |

---

## Support

For issues setting up secrets:

1. Check GitHub Docs: <https://docs.github.com/en/actions/security-guides/encrypted-secrets>
2. Review workflow logs in Actions tab
3. Test SSH locally before adding to GitHub
4. Verify all required secrets are added

---

**Created**: 15 March 2026
**Status**: ✅ Complete
