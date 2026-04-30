output "vpc_id" {
  description = "VPC ID"
  value       = module.vpc.vpc_id
}

output "ecs_cluster_id" {
  description = "ECS Cluster ID"
  value       = aws_ecs_cluster.catvrf.id
}

output "ecs_service_blue_name" {
  description = "ECS Service (Blue) name"
  value       = aws_ecs_service.catvrf_blue.name
}

output "ecs_service_green_name" {
  description = "ECS Service (Green) name"
  value       = aws_ecs_service.catvrf_green.name
}

output "alb_dns_name" {
  description = "Application Load Balancer DNS name"
  value       = aws_lb.catvrf.dns_name
}

output "alb_zone_id" {
  description = "Application Load Balancer zone ID"
  value       = aws_lb.catvrf.zone_id
}

output "rds_cluster_endpoint" {
  description = "RDS Cluster endpoint"
  value       = aws_rds_cluster.catvrf.endpoint
  sensitive   = true
}

output "redis_endpoint" {
  description = "ElastiCache Redis endpoint"
  value       = aws_elasticache_replication_group.catvrf.primary_endpoint_address
  sensitive   = true
}

output "cloudwatch_log_group" {
  description = "CloudWatch Log Group name"
  value       = aws_cloudwatch_log_group.catvrf.name
}
