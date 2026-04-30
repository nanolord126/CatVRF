# Terraform configuration for Prometheus deployment
# CatVRF Monitoring Stack

resource "helm_release" "prometheus" {
  name       = "prometheus"
  repository = "https://prometheus-community.github.io/helm-charts"
  chart      = "prometheus"
  namespace  = "monitoring"
  version    = "19.7.2"

  set {
    name  = "server.replicaCount"
    value = "2"
  }

  set {
    name  = "server.service.type"
    value = "ClusterIP"
  }

  set {
    name  = "server.service.port"
    value = "9090"
  }

  # Alertmanager integration
  set {
    name  = "server.alertmanagers[0].name"
    value = "alertmanager"
  }

  set {
    name  = "server.alertmanagers[0].namespace"
    value = "monitoring"
  }

  set {
    name  = "server.alertmanagers[0].port"
    value = "9093"
  }

  # Retention
  set {
    name  = "server.retention"
    value = "15d"
  }

  set {
    name  = "server.persistentVolume.enabled"
    value = "true"
  }

  set {
    name  = "server.persistentVolume.size"
    value = "50Gi"
  }

  # Resources
  set {
    name  = "server.resources.requests.cpu"
    value = "500m"
  }

  set {
    name  = "server.resources.requests.memory"
    value = "1Gi"
  }

  set {
    name  = "server.resources.limits.cpu"
    value = "2000m"
  }

  set {
    name  = "server.resources.limits.memory"
    value = "4Gi"
  }

  # ServiceMonitor
  set {
    name  = "kube-state-metrics.enabled"
    value = "true"
  }

  set {
    name  = "prometheus-node-exporter.enabled"
    value = "true"
  }

  set {
    name  = "prometheus-pushgateway.enabled"
    value = "true"
  }
}

# ConfigMap for Prometheus configuration
resource "kubernetes_config_map" "prometheus_config" {
  metadata {
    name      = "prometheus-config"
    namespace = "monitoring"
  }

  data = {
    "prometheus.yml" = file("${path.module}/../prometheus/prometheus.yml")
  }
}

# ConfigMap for alert rules
resource "kubernetes_config_map" "prometheus_rules" {
  metadata {
    name      = "prometheus-rules"
    namespace = "monitoring"
  }

  data = {
    "alerts.yml" = file("${path.module}/../alertmanager/rules/alerts.yml")
  }
}
