# Terraform configuration for Grafana deployment
# CatVRF Monitoring Stack

resource "helm_release" "grafana" {
  name       = "grafana"
  repository = "https://grafana.github.io/helm-charts"
  chart      = "grafana"
  namespace  = "monitoring"
  version    = "6.55.0"

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
    value = "3000"
  }

  # Admin credentials
  set {
    name  = "adminPassword"
    value = var.grafana_admin_password
  }

  # Persistence
  set {
    name  = "persistence.enabled"
    value = "true"
  }

  set {
    name  = "persistence.size"
    value = "10Gi"
  }

  # Resources
  set {
    name  = "resources.requests.cpu"
    value = "200m"
  }

  set {
    name  = "resources.requests.memory"
    value = "256Mi"
  }

  set {
    name  = "resources.limits.cpu"
    value = "1000m"
  }

  set {
    name  "resources.limits.memory"
    value = "1Gi"
  }

  # Prometheus datasource
  set {
    name  = "datasources.datasources[0].name"
    value = "Prometheus"
  }

  set {
    name  = "datasources.datasources[0].type"
    value = "prometheus"
  }

  set {
    name  = "datasources.datasources[0].url"
    value = "http://prometheus.monitoring.svc.cluster.local:9090"
  }

  set {
    name  = "datasources.datasources[0].access"
    value = "proxy"
  }

  set {
    name  = "datasources.datasources[0].isDefault"
    value = "true"
  }

  # Dashboard provisioning
  set {
    name  = "dashboards.enabled"
    value = "true"
  }

  set {
    name  = "dashboards.label"
    value = "catvrf"
  }

  set {
    name  = "dashboards.labelValue"
    value = "catvrf"
  }
}

# ConfigMap for CatVRF dashboard
resource "kubernetes_config_map" "grafana_dashboards" {
  metadata {
    name      = "grafana-dashboards"
    namespace = "monitoring"
    labels = {
      grafana_dashboard = "1"
    }
  }

  data = {
    "catvrf-queues-v2.json" = file("${path.module}/../grafana/dashboards/catvrf-queues-v2.json")
  }
}

# Variables
variable "grafana_admin_password" {
  description = "Admin password for Grafana"
  type        = string
  sensitive   = true
}
