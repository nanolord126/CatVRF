# CatVRF Infrastructure as Code (Terraform)
# Production-ready infrastructure for CatVRF medical marketplace
# Provider: AWS (can be adapted for other cloud providers)

terraform {
  required_version = ">= 1.5.0"
  
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
    
    docker = {
      source  = "kreuzwerker/docker"
      version = "~> 3.0"
    }
  }

  backend "s3" {
    bucket         = "catvrf-terraform-state"
    key            = "production/terraform.tfstate"
    region         = "eu-central-1"
    encrypt        = true
    dynamodb_table = "catvrf-terraform-locks"
  }
}

provider "aws" {
  region = var.aws_region

  default_tags {
    tags = {
      Project     = "CatVRF"
      Environment = var.environment
      ManagedBy   = "Terraform"
    }
  }
}

# VPC Configuration
module "vpc" {
  source  = "terraform-aws-modules/vpc/aws"
  version = "5.1.2"

  name = "${var.project_name}-vpc-${var.environment}"
  cidr = var.vpc_cidr

  azs              = var.availability_zones
  private_subnets  = var.private_subnet_cidrs
  public_subnets   = var.public_subnet_cidrs
  database_subnets = var.database_subnet_cidrs

  enable_nat_gateway     = true
  single_nat_gateway     = var.environment == "staging"
  one_nat_gateway_per_az = var.environment == "production"

  enable_vpn_gateway = false
  enable_dns_hostnames = true
  enable_dns_support   = true

  # Database subnet group
  create_database_subnet_group = true

  tags = {
    Environment = var.environment
  }
}

# ECS Cluster for CatVRF
resource "aws_ecs_cluster" "catvrf" {
  name = "${var.project_name}-cluster-${var.environment}"

  setting {
    name  = "containerInsights"
    value = "enabled"
  }
}

# ECS Task Definition for CatVRF
resource "aws_ecs_task_definition" "catvrf" {
  family                   = "${var.project_name}-task-${var.environment}"
  network_mode             = "awsvpc"
  requires_compatibilities = ["FARGATE"]
  cpu                      = var.task_cpu
  memory                   = var.task_memory
  execution_role_arn       = aws_iam_role.ecs_execution_role.arn
  task_role_arn            = aws_iam_role.ecs_task_role.arn

  container_definitions = jsonencode([
    {
      name      = "catvrf-app"
      image     = "${var.ecr_repository_url}:${var.image_tag}"
      cpu       = var.task_cpu
      memory    = var.task_memory
      essential = true

      portMappings = [
        {
          containerPort = 80
          protocol      = "tcp"
        }
      ]

      environment = [
        {
          name  = "APP_ENV"
          value = var.environment
        },
        {
          name  = "APP_DEBUG"
          value = var.environment == "production" ? "false" : "true"
        },
        {
          name  = "DB_HOST"
          value = aws_rds_cluster.catvrf.endpoint
        },
        {
          name  = "REDIS_HOST"
          value = aws_elasticache_replication_group.catvrf.primary_endpoint_address
        }
      ]

      secrets = [
        {
          name      = "APP_KEY"
          valueFrom = aws_secretsmanager_secret.app_key.arn
        },
        {
          name      = "DB_PASSWORD"
          valueFrom = aws_secretsmanager_secret.db_password.arn
        }
      ]

      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.catvrf.name
          "awslogs-region"        = var.aws_region
          "awslogs-stream-prefix" = "ecs"
        }
      }

      healthCheck = {
        command     = ["CMD-SHELL", "curl -f http://localhost/health || exit 1"]
        interval    = 30
        timeout     = 5
        retries     = 3
        startPeriod = 60
      }
    }
  ])
}

# ECS Service (Blue-Green Deployment)
resource "aws_ecs_service" "catvrf_blue" {
  name            = "${var.project_name}-blue-${var.environment}"
  cluster         = aws_ecs_cluster.catvrf.id
  task_definition = aws_ecs_task_definition.catvrf.arn
  desired_count   = var.desired_count
  launch_type     = "FARGATE"

  network_configuration {
    subnets          = module.vpc.private_subnets
    security_groups  = [aws_security_group.catvrf.id]
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.catvrf_blue.arn
    container_name   = "catvrf-app"
    container_port   = 80
  }

  deployment_controller {
    type = "CODE_DEPLOY"
  }
}

resource "aws_ecs_service" "catvrf_green" {
  name            = "${var.project_name}-green-${var.environment}"
  cluster         = aws_ecs_cluster.catvrf.id
  task_definition = aws_ecs_task_definition.catvrf.arn
  desired_count   = 0  # Green starts with 0 tasks
  launch_type     = "FARGATE"

  network_configuration {
    subnets          = module.vpc.private_subnets
    security_groups  = [aws_security_group.catvrf.id]
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.catvrf_green.arn
    container_name   = "catvrf-app"
    container_port   = 80
  }

  deployment_controller {
    type = "CODE_DEPLOY"
  }
}

# Application Load Balancer
resource "aws_lb" "catvrf" {
  name               = "${var.project_name}-alb-${var.environment}"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb.id]
  subnets            = module.vpc.public_subnets

  enable_deletion_protection = var.environment == "production"

  access_logs {
    bucket  = aws_s3_bucket.logs.id
    prefix  = "alb-logs"
    enabled = true
  }
}

# ALB Target Groups
resource "aws_lb_target_group" "catvrf_blue" {
  name        = "${var.project_name}-blue-tg-${var.environment}"
  port        = 80
  protocol    = "HTTP"
  vpc_id      = module.vpc.vpc_id
  target_type = "ip"

  health_check {
    enabled             = true
    healthy_threshold   = 2
    interval            = 30
    matcher             = "200"
    path                = "/health"
    port                = "traffic-port"
    protocol            = "HTTP"
    timeout             = 5
    unhealthy_threshold = 3
  }
}

resource "aws_lb_target_group" "catvrf_green" {
  name        = "${var.project_name}-green-tg-${var.environment}"
  port        = 80
  protocol    = "HTTP"
  vpc_id      = module.vpc.vpc_id
  target_type = "ip"

  health_check {
    enabled             = true
    healthy_threshold   = 2
    interval            = 30
    matcher             = "200"
    path                = "/health"
    port                = "traffic-port"
    protocol            = "HTTP"
    timeout             = 5
    unhealthy_threshold = 3
  }
}

# ALB Listener
resource "aws_lb_listener" "catvrf" {
  load_balancer_arn = aws_lb.catvrf.arn
  port              = 443
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS-1-2-2017-01"
  certificate_arn   = var.acm_certificate_arn

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.catvrf_blue.arn
  }
}

# RDS PostgreSQL Cluster
resource "aws_rds_cluster" "catvrf" {
  engine                = "aurora-postgresql"
  engine_version        = "15.4"
  database_name         = "catvrf"
  master_username       = "catvrf_admin"
  cluster_identifier    = "${var.project_name}-db-${var.environment}"
  db_subnet_group_name  = module.vpc.database_subnet_group_name
  vpc_security_group_ids = [aws_security_group.database.id]

  backup_retention_period = var.environment == "production" ? 30 : 7
  preferred_backup_window = "03:00-04:00"
  skip_final_snapshot     = var.environment != "production"

  deletion_protection = var.environment == "production"

  serverlessv2_scaling_configuration {
    min_capacity = var.environment == "production" ? 2 : 0.5
    max_capacity = var.environment == "production" ? 16 : 4
  }
}

# ElastiCache Redis
resource "aws_elasticache_replication_group" "catvrf" {
  replication_group_id          = "${var.project_name}-redis-${var.environment}"
  replication_group_description = "CatVRF Redis Cluster"
  node_type                     = var.environment == "production" ? "cache.r6g.large" : "cache.t3.medium"
  number_cache_clusters         = length(var.availability_zones)
  engine                        = "redis"
  engine_version                = "7.0"
  parameter_group_name          = "default.redis7"
  subnet_group_name             = aws_elasticache_subnet_group.catvrf.name
  security_group_ids            = [aws_security_group.redis.id]

  automatic_failover_enabled = var.environment == "production"
  multi_az_enabled           = var.environment == "production"

  at_rest_encryption_enabled = true
  transit_encryption_enabled = true
  auth_token                 = var.redis_auth_token
}

# CloudWatch Log Groups
resource "aws_cloudwatch_log_group" "catvrf" {
  name              = "/ecs/${var.project_name}-${var.environment}"
  retention_in_days = var.environment == "production" ? 30 : 7
}

# S3 Buckets
resource "aws_s3_bucket" "logs" {
  bucket = "${var.project_name}-logs-${var.environment}-${random_id.bucket_suffix.hex}"
}

resource "aws_s3_bucket" "storage" {
  bucket = "${var.project_name}-storage-${var.environment}-${random_id.bucket_suffix.hex}"
}

# Security Groups
resource "aws_security_group" "catvrf" {
  name_prefix = "${var.project_name}-sg-${environment}-"
  vpc_id      = module.vpc.vpc_id

  ingress {
    from_port       = 80
    to_port         = 80
    protocol        = "tcp"
    security_groups = [aws_security_group.alb.id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

resource "aws_security_group" "alb" {
  name_prefix = "${var.project_name}-alb-sg-${environment}-"
  vpc_id      = module.vpc.vpc_id

  ingress {
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

resource "aws_security_group" "database" {
  name_prefix = "${var.project_name}-db-sg-${environment}-"
  vpc_id      = module.vpc.vpc_id

  ingress {
    from_port       = 5432
    to_port         = 5432
    protocol        = "tcp"
    security_groups = [aws_security_group.catvrf.id]
  }
}

resource "aws_security_group" "redis" {
  name_prefix = "${var.project_name}-redis-sg-${environment}-"
  vpc_id      = module.vpc.vpc_id

  ingress {
    from_port       = 6379
    to_port         = 6379
    protocol        = "tcp"
    security_groups = [aws_security_group.catvrf.id]
  }
}

# IAM Roles
resource "aws_iam_role" "ecs_execution_role" {
  name = "${var.project_name}-ecs-execution-role-${var.environment}"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Action = "sts:AssumeRole"
        Effect = "Allow"
        Principal = {
          Service = "ecs-tasks.amazonaws.com"
        }
      }
    ]
  })
}

resource "aws_iam_role_policy_attachment" "ecs_execution_role_policy" {
  role       = aws_iam_role.ecs_execution_role.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonECSTaskExecutionRolePolicy"
}

resource "aws_iam_role" "ecs_task_role" {
  name = "${var.project_name}-ecs-task-role-${var.environment}"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Action = "sts:AssumeRole"
        Effect = "Allow"
        Principal = {
          Service = "ecs-tasks.amazonaws.com"
        }
      }
    ]
  })
}

# Secrets Manager
resource "aws_secretsmanager_secret" "app_key" {
  name = "${var.project_name}/app-key/${var.environment}"
}

resource "aws_secretsmanager_secret" "db_password" {
  name = "${var.project_name}/db-password/${var.environment}"
}

resource "random_id" "bucket_suffix" {
  byte_length = 4
}
