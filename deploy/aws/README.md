# AWS prerequisites (one-time)

These resources are set up **once** outside of CI. Steps assume `aws` CLI is configured for the target account.

## 1. ECR repository
```bash
aws ecr create-repository --repository-name phpenterpriseblog --image-scanning-configuration scanOnPush=true
```

## 2. GitHub OIDC provider
If not already present:
```bash
aws iam create-open-id-connect-provider \
  --url https://token.actions.githubusercontent.com \
  --client-id-list sts.amazonaws.com \
  --thumbprint-list 6938fd4d98bab03faadb97b34396831e3780aea1
```

## 3. IAM roles (OIDC-assumable)

### `phpenterpriseblog-ci-ecr`
Trust policy restricts to `repo:<OWNER>/phpenterpriseblog:ref:refs/heads/main` and `repo:<OWNER>/phpenterpriseblog:ref:refs/tags/v*`.
Permissions: `ecr:GetAuthorizationToken`, `ecr:BatchCheckLayerAvailability`, `ecr:PutImage`, `ecr:InitiateLayerUpload`, `ecr:UploadLayerPart`, `ecr:CompleteLayerUpload`, scoped to the `phpenterpriseblog` repository ARN only.

### `phpenterpriseblog-cd-eks`
Trust policy restricts to `repo:<OWNER>/phpenterpriseblog:ref:refs/tags/v*`.
Permissions: `eks:DescribeCluster`, `secretsmanager:GetSecretValue` on `arn:aws:secretsmanager:*:*:secret:phpenterpriseblog/*`.

## 4. EKS cluster
Any EKS ≥ 1.30 cluster. Create namespaces:
```bash
kubectl create namespace phpenterpriseblog-staging
kubectl create namespace phpenterpriseblog-prod
```

## 5. RDS MySQL 8
One instance per environment. Store credentials in AWS Secrets Manager:
- `phpenterpriseblog/staging/db` → JSON: `{ "DB_HOST", "DB_PORT", "DB_DATABASE", "DB_USERNAME", "DB_PASSWORD" }`
- `phpenterpriseblog/prod/db`

## 6. ElastiCache Redis (TLS)
One cluster per env. Secret:
- `phpenterpriseblog/{env}/redis` → JSON: `{ "REDIS_HOST", "REDIS_PORT", "REDIS_PASSWORD" }`

## 7. Cluster add-ons
- AWS Load Balancer Controller
- External Secrets Operator (configured with the IRSA-bound `ClusterSecretStore` pointing to AWS Secrets Manager)
- (optional) Metrics Server for HPA

## 8. GitHub repo variables
Set as `Settings → Secrets and variables → Actions → Variables`:
- `AWS_ACCOUNT` — the account id
- `AWS_REGION` — e.g. `us-east-1`
- `ECR_REGISTRY` — `<account>.dkr.ecr.<region>.amazonaws.com`
- `EKS_CLUSTER` — cluster name

## 9. GitHub Environments
Create:
- `staging` — no required reviewers
- `production` — required reviewers ON
