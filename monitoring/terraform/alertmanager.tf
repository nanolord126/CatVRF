# Terraform configuration for Alertmanager deployment
# CatVRF Monitoring Stack

resource "helm_release" "alertmanager" {
  name       = "alertmanager"
  repository = "https://prometheus-community.github.io/helm-charts"
  chart      = "alertmanager"
  namespace  = "monitoring"
  version    = "0.27.0"

  set {
    name  = "replicaCount"
    value = "2"
  }

  set {
    name  = "service.type"
    value = "ClusterIP"
  }

  set {
    name  = "service.port"
    value = "9093"
  }

  set {
    name  = "config.global.slack_api_url"
    value = var.slack_webhook_url
  }

  set {
    name  = "config.global.smtp_smarthost"
    value = var.smtp_smarthost
  }

  set {
    name  = "config.global.smtp_from"
    value = var.smtp_from
  }

  set {
    name  = "config.global.smtp_auth_username"
    value = var.smtp_auth_username
  }

  set {
    name  = "config.global.smtp_auth_password"
    value = var.smtp_auth_password
  }

  set {
    name  = "config.route.receiver"
    value = "default"
  }

  set {
    name  = "serviceMonitor.enabled"
    value = "true"
  }

  # Resources
  set {
    name  = "resources.requests.cpu"
    value = "100m"
  }

  set {
    name  = "resources.requests.memory"
    value = "128Mi"
  }

  set {
    name  = "resources.limits.cpu"
    value = "500m"
  }

  set {
    name  = "resources.limits.memory"
    value = "512Mi"
  }

  # Persistence
  set {
    name  = "persistence.enabled"
    value = "true"
  }

  set {
    name  = "persistence.size"
    value = "2Gi"
  }
}

# ConfigMap for alertmanager configuration
resource "kubernetes_config_map" "alertmanager_config" {
  metadata {
    name      = "alertmanager-config"
    namespace = "monitoring"
  }

  data = {
    "alertmanager.yml" = file("${path.module}/../alertmanager/alertmanager.yml")
  }
}

# ConfigMap for alert rules
resource "kubernetes_config_map" "alertmanager_rules" {
  metadata {
    name      = "alertmanager-rules"
    namespace = "monitoring"
  }

  data = {
    "alerts.yml" = file("${path.module}/../alertmanager/rules/alerts.yml")
  }
}

# ConfigMap for templates
resource "kubernetes_config_map" "alertmanager_templates" {
  metadata {
    name      = "alertmanager-templates"
    namespace = "monitoring"
  }

  data = {
    "slack.tmpl"   = file("${path.module}/../alertmanager/templates/slack.tmpl")
    "telegram.tmpl" = file("${path.module}/../alertmanager/templates/telegram.tmpl")
    "email.tmpl"   = file("${path.module}/../alertmanager/templates/email.tmpl")
  }
}

# Variables
variable "slack_webhook_url" {
  description = "Slack webhook URL for alerts"
  type        = string
  sensitive   = true
}

variable "smtp_smarthost" {
  description = "SMTP server address"
  type        = string
  default     = "smtp.gmail.com:587"
}

variable "smtp_from" {
  description = "From address for email alerts"
  type        = string
  default     = "alerts@catvrf.ru"
}

variable "smtp_auth_username" {
  description = "SMTP authentication username"
  type        = string
  sensitive   = true
}

variable "smtp_auth_password" {
  description = "SMTP authentication password"
  type        = string
  sensitive   = true
}
