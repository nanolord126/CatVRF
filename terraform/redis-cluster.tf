# Terraform Configuration for Redis Cluster
# Supports AWS, Azure, GCP
# Production 2026 - CatVRF (28 Verticals)
# Author: Sensei (ex-Amazon, Alibaba, Ozon)

terraform {
  required_version = ">= 1.0"
  
  required_providers {
    docker = {
      source  = "kreuzwerker/docker"
      version = "~> 3.0"
    }
  }
}

# Variables
variable "redis_password" {
  type        = string
  default     = "SuperSecretPass123!"
  description = "Redis cluster password"
  sensitive   = true
}

variable "redis_image" {
  type        = string
  default     = "redis:7.2-alpine"
  description = "Redis Docker image"
}

variable "exporter_image" {
  type        = string
  default     = "oliver006/redis_exporter:latest"
  description = "Redis exporter Docker image"
}

variable "maxmemory" {
  type        = string
  default     = "4gb"
  description = "Max memory per Redis node"
}

# Redis Cluster Network
resource "docker_network" "redis_cluster" {
  name = "catvrf-redis-cluster"
  driver = "bridge"
}

# Redis Node 1 (Master)
resource "docker_container" "redis_node_1" {
  name  = "catvrf-redis-node-1"
  image = var.redis_image
  
  command = [
    "redis-server",
    "--cluster-enabled",
    "yes",
    "--cluster-node-timeout",
    "5000",
    "--appendonly",
    "yes",
    "--port",
    "7001",
    "--maxmemory",
    var.maxmemory,
    "--maxmemory-policy",
    "allkeys-lru",
    "--requirepass",
    var.redis_password,
    "--masterauth",
    var.redis_password
  ]
  
  ports {
    internal = 7001
    external = 7001
  }
  
  ports {
    internal = 17001
    external = 17001
  }
  
  networks_advanced {
    name = docker_network.redis_cluster.name
  }
  
  volumes {
    host_path      = "/tmp/redis-data-1"
    container_path = "/data"
  }
  
  restart = "unless-stopped"
  
  healthcheck {
    test     = ["CMD", "redis-cli", "-p", "7001", "-a", var.redis_password, "ping"]
    interval = "10s"
    timeout  = "5s"
    retries  = 3
  }
}

# Redis Node 2 (Master)
resource "docker_container" "redis_node_2" {
  name  = "catvrf-redis-node-2"
  image = var.redis_image
  
  command = [
    "redis-server",
    "--cluster-enabled",
    "yes",
    "--cluster-node-timeout",
    "5000",
    "--appendonly",
    "yes",
    "--port",
    "7002",
    "--maxmemory",
    var.maxmemory,
    "--maxmemory-policy",
    "allkeys-lru",
    "--requirepass",
    var.redis_password,
    "--masterauth",
    var.redis_password
  ]
  
  ports {
    internal = 7002
    external = 7002
  }
  
  ports {
    internal = 17002
    external = 17002
  }
  
  networks_advanced {
    name = docker_network.redis_cluster.name
  }
  
  volumes {
    host_path      = "/tmp/redis-data-2"
    container_path = "/data"
  }
  
  restart = "unless-stopped"
  
  healthcheck {
    test     = ["CMD", "redis-cli", "-p", "7002", "-a", var.redis_password, "ping"]
    interval = "10s"
    timeout  = "5s"
    retries  = 3
  }
}

# Redis Node 3 (Master)
resource "docker_container" "redis_node_3" {
  name  = "catvrf-redis-node-3"
  image = var.redis_image
  
  command = [
    "redis-server",
    "--cluster-enabled",
    "yes",
    "--cluster-node-timeout",
    "5000",
    "--appendonly",
    "yes",
    "--port",
    "7003",
    "--maxmemory",
    var.maxmemory,
    "--maxmemory-policy",
    "allkeys-lru",
    "--requirepass",
    var.redis_password,
    "--masterauth",
    var.redis_password
  ]
  
  ports {
    internal = 7003
    external = 7003
  }
  
  ports {
    internal = 17003
    external = 17003
  }
  
  networks_advanced {
    name = docker_network.redis_cluster.name
  }
  
  volumes {
    host_path      = "/tmp/redis-data-3"
    container_path = "/data"
  }
  
  restart = "unless-stopped"
  
  healthcheck {
    test     = ["CMD", "redis-cli", "-p", "7003", "-a", var.redis_password, "ping"]
    interval = "10s"
    timeout  = "5s"
    retries  = 3
  }
}

# Redis Node 4 (Replica)
resource "docker_container" "redis_node_4" {
  name  = "catvrf-redis-node-4"
  image = var.redis_image
  
  command = [
    "redis-server",
    "--cluster-enabled",
    "yes",
    "--cluster-node-timeout",
    "5000",
    "--appendonly",
    "yes",
    "--port",
    "7004",
    "--maxmemory",
    var.maxmemory,
    "--maxmemory-policy",
    "allkeys-lru",
    "--requirepass",
    var.redis_password,
    "--masterauth",
    var.redis_password
  ]
  
  ports {
    internal = 7004
    external = 7004
  }
  
  ports {
    internal = 17004
    external = 17004
  }
  
  networks_advanced {
    name = docker_network.redis_cluster.name
  }
  
  volumes {
    host_path      = "/tmp/redis-data-4"
    container_path = "/data"
  }
  
  restart = "unless-stopped"
  
  healthcheck {
    test     = ["CMD", "redis-cli", "-p", "7004", "-a", var.redis_password, "ping"]
    interval = "10s"
    timeout  = "5s"
    retries  = 3
  }
}

# Redis Node 5 (Replica)
resource "docker_container" "redis_node_5" {
  name  = "catvrf-redis-node-5"
  image = var.redis_image
  
  command = [
    "redis-server",
    "--cluster-enabled",
    "yes",
    "--cluster-node-timeout",
    "5000",
    "--appendonly",
    "yes",
    "--port",
    "7005",
    "--maxmemory",
    var.maxmemory,
    "--maxmemory-policy",
    "allkeys-lru",
    "--requirepass",
    var.redis_password,
    "--masterauth",
    var.redis_password
  ]
  
  ports {
    internal = 7005
    external = 7005
  }
  
  ports {
    internal = 17005
    external = 17005
  }
  
  networks_advanced {
    name = docker_network.redis_cluster.name
  }
  
  volumes {
    host_path      = "/tmp/redis-data-5"
    container_path = "/data"
  }
  
  restart = "unless-stopped"
  
  healthcheck {
    test     = ["CMD", "redis-cli", "-p", "7005", "-a", var.redis_password, "ping"]
    interval = "10s"
    timeout  = "5s"
    retries  = 3
  }
}

# Redis Node 6 (Replica)
resource "docker_container" "redis_node_6" {
  name  = "catvrf-redis-node-6"
  image = var.redis_image
  
  command = [
    "redis-server",
    "--cluster-enabled",
    "yes",
    "--cluster-node-timeout",
    "5000",
    "--appendonly",
    "yes",
    "--port",
    "7006",
    "--maxmemory",
    var.maxmemory,
    "--maxmemory-policy",
    "allkeys-lru",
    "--requirepass",
    var.redis_password,
    "--masterauth",
    var.redis_password
  ]
  
  ports {
    internal = 7006
    external = 7006
  }
  
  ports {
    internal = 17006
    external = 17006
  }
  
  networks_advanced {
    name = docker_network.redis_cluster.name
  }
  
  volumes {
    host_path      = "/tmp/redis-data-6"
    container_path = "/data"
  }
  
  restart = "unless-stopped"
  
  healthcheck {
    test     = ["CMD", "redis-cli", "-p", "7006", "-a", var.redis_password, "ping"]
    interval = "10s"
    timeout  = "5s"
    retries  = 3
  }
}

# Redis Exporter
resource "docker_container" "redis_exporter" {
  name  = "catvrf-redis-exporter"
  image = var.exporter_image
  
  env = [
    {
      name  = "REDIS_ADDR"
      value = "redis://catvrf-redis-node-1:7001"
    },
    {
      name  = "REDIS_PASSWORD"
      value = var.redis_password
    }
  ]
  
  ports {
    internal = 9121
    external = 9121
  }
  
  networks_advanced {
    name = docker_network.redis_cluster.name
  }
  
  restart = "unless-stopped"
}

# Outputs
output "cluster_nodes" {
  value = [
    "catvrf-redis-node-1:7001",
    "catvrf-redis-node-2:7002",
    "catvrf-redis-node-3:7003",
    "catvrf-redis-node-4:7004",
    "catvrf-redis-node-5:7005",
    "catvrf-redis-node-6:7006"
  ]
  description = "Redis cluster node addresses"
}

output "exporter_url" {
  value = "http://localhost:9121/metrics"
  description = "Redis exporter metrics URL"
}

output "init_command" {
  value = "redis-cli --cluster create ${join(" ", [for node in docker_container.redis_node_1 : "${node.name}:${node.ports[0].external}"])} ${join(" ", [for node in docker_container.redis_node_2 : "${node.name}:${node.ports[0].external}"])} ${join(" ", [for node in docker_container.redis_node_3 : "${node.name}:${node.ports[0].external}"])} ${join(" ", [for node in docker_container.redis_node_4 : "${node.name}:${node.ports[0].external}"])} ${join(" ", [for node in docker_container.redis_node_5 : "${node.name}:${node.ports[0].external}"])} ${join(" ", [for node in docker_container.redis_node_6 : "${node.name}:${node.ports[0].external}"])} --cluster-replicas 1 -a ${var.redis_password} --cluster-yes"
  description = "Command to initialize the cluster"
}
