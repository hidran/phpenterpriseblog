# Presentation demo — spin up & tear down

A **lean** AWS environment for a live demo: VPC + EKS + ECR + GitHub OIDC/IAM,
with MySQL and Redis running **in-cluster as pods** (no RDS/ElastiCache) to keep
cost and create/destroy time low. One script builds it, one tears it all down.

> For the full production-shaped infra (RDS, ElastiCache, Secrets Manager,
> External Secrets) follow [`../README.md`](../README.md) instead.

## Prerequisites
- `aws` CLI, authenticated to the target account (`aws sts get-caller-identity` works)
- `docker` (with buildx), `kubectl`, `helm`
- The CloudFormation creates **named IAM roles** → deploy uses `CAPABILITY_NAMED_IAM`.

## What gets created
`deploy/aws/cloudformation/eks-demo.yaml` (one stack, default name `phpenterpriseblog-demo`):
- VPC with two public subnets (no NAT gateway)
- EKS cluster + a small managed node group (default `t3.medium` ×2)
- ECR repository (`EmptyOnDelete: true` so teardown removes it with its images)
- GitHub Actions OIDC provider (skipped if you pass `OIDC_PROVIDER_ARN`)
- Two IAM roles: `…-ci-ecr` (push images) and `…-cd-eks` (deploy)

MySQL, Redis and the `phpenterpriseblog-db` / `-redis` Secrets come from
[`in-cluster-deps.yaml`](in-cluster-deps.yaml); the app is deployed with the
project Helm chart plus [`values.demo.yaml`](values.demo.yaml).

## Build it
```bash
./up.sh                      # ~20 min (EKS is the slow part)
# optional overrides:
# REGION=eu-west-1 NODE_TYPE=t3.small ./up.sh
# OIDC_PROVIDER_ARN=arn:aws:iam::123456789012:oidc-provider/token.actions.githubusercontent.com ./up.sh
```
Then view the app:
```bash
kubectl -n phpenterpriseblog-demo port-forward svc/phpenterpriseblog 8080:80
open http://localhost:8080        # health check: http://localhost:8080/healthz
```

## Tear it down
```bash
./down.sh                    # uninstall release, then delete the stack (~15 min)
```

## Important: teardown order
`down.sh` runs `helm uninstall` **before** `delete-stack`. If you change the app
to expose a **cloud load balancer** (Service `type: LoadBalancer` or the ALB
ingress), that LB is created by Kubernetes *outside* CloudFormation; it must be
deleted first or its leaked network interfaces will block the VPC from
deleting. The default demo uses `port-forward` (no cloud LB), so this is only a
caveat if you opt into a LoadBalancer. If a `delete-stack` ever stalls on the
VPC, check for a leftover ELB/ALB and delete it, then retry.

## Cost
EKS control plane (~$0.10/hr) + the node EC2 instances run only while the stack
exists. `down.sh` removes everything, so cost stops at teardown. Bring it up
shortly before the talk and tear it down right after.

## Optional: exercise the release pipeline
The stack outputs `CiEcrRoleArn`, `CdEksRoleArn`, `EcrRepositoryUri` and
`ClusterName`. To demo `.github/workflows/release.yml`, set the repo's Actions
variables (`AWS_ACCOUNT`, `AWS_REGION`, `ECR_REGISTRY`, `EKS_CLUSTER`) and the
`staging`/`production` environments, then push a `v*` tag.
