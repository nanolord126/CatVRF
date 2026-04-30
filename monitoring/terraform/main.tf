# CatVRF Monitoring Stack - Terraform Configuration
# Provider: Kubernetes + Helm

terraform {
  required_version = ">= 1.0"
  
  required_providers {
    helm = {
      source  = "hashicorp/helm"
      version = ">= 2.10.0"
    }
    kubernetes = {
      source  = "hashicorp/kubernetes"
      version = ">= 2.20.0"
    }
  }
}

provider "helm" {
  kubernetes {
    config_path = var.kubeconfig_path
  }
}

provider "kubernetes" {
  config_path = var.kubeconfig_path
}

# Variables
variable "kubeconfig_path" {
  description = "Path to kubeconfig file"
  type        = string
  default     = "~/.kube/config"
}

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

variable "grafana_admin_password" {
  description = "Admin password for Grafana"
  type        = string
  sensitive   = true
}

# Outputs
output "grafana_url" {
  description = "Grafana URL"
  value       = "http://grafana.monitoring.svc.cluster.local:3000"
}

output "prometheus_url" {
  description = "Prometheus URL"
  value       = "http://prometheus.monitoring.svc.cluster.local:9090"
}

output "alertmanager_url" {
  description = "Alertmanager URL"
  value       = "http://alertmanager.monitoring.svc-cluster.local:9093"
}
