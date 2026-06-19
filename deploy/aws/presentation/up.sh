#!/usr/bin/env bash
# CONSTRUCTIVE: build the AWS demo infra, push the image, deploy the app.
#
# Prereqs (on the presenter's machine): aws CLI (configured), docker (buildx),
# kubectl, helm. Run from anywhere.
#
# Tunables (env vars):
#   PROJECT=phpenterpriseblog  REGION=<aws region>  GITHUB_OWNER=hidran
#   STACK=phpenterpriseblog-demo  IMAGE_TAG=demo  NODE_TYPE=t3.medium
#   OIDC_PROVIDER_ARN=<arn>   # set if the GitHub OIDC provider already exists
set -euo pipefail

PROJECT="${PROJECT:-phpenterpriseblog}"
STACK="${STACK:-phpenterpriseblog-demo}"
GITHUB_OWNER="${GITHUB_OWNER:-hidran}"
GITHUB_REPO="${GITHUB_REPO:-phpenterpriseblog}"
IMAGE_TAG="${IMAGE_TAG:-demo}"
NODE_TYPE="${NODE_TYPE:-t3.medium}"
REGION="${AWS_REGION:-$(aws configure get region 2>/dev/null || echo us-east-1)}"
NAMESPACE="phpenterpriseblog-demo"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../../.." && pwd)"

echo ">> Region: $REGION  Stack: $STACK  Project: $PROJECT"

# 1. Build the infra (idempotent; safe to re-run).
echo ">> [1/6] Deploying CloudFormation stack (EKS + ECR + IAM/OIDC)..."
aws cloudformation deploy \
  --region "$REGION" \
  --stack-name "$STACK" \
  --template-file "$SCRIPT_DIR/../cloudformation/eks-demo.yaml" \
  --capabilities CAPABILITY_NAMED_IAM \
  --parameter-overrides \
    ProjectName="$PROJECT" \
    GitHubOwner="$GITHUB_OWNER" \
    GitHubRepo="$GITHUB_REPO" \
    NodeInstanceType="$NODE_TYPE" \
    GitHubOidcProviderArn="${OIDC_PROVIDER_ARN:-}"

# 2. Read stack outputs.
get_output() {
  aws cloudformation describe-stacks --region "$REGION" --stack-name "$STACK" \
    --query "Stacks[0].Outputs[?OutputKey=='$1'].OutputValue" --output text
}
CLUSTER="$(get_output ClusterName)"
ECR_URI="$(get_output EcrRepositoryUri)"
echo ">> Cluster: $CLUSTER  ECR: $ECR_URI"

# 3. Point kubectl at the new cluster.
echo ">> [2/6] Updating kubeconfig..."
aws eks update-kubeconfig --region "$REGION" --name "$CLUSTER"

# 4. Build + push the image to ECR (linux/amd64 to match the EKS nodes).
echo ">> [3/6] Building and pushing image to ECR..."
aws ecr get-login-password --region "$REGION" \
  | docker login --username AWS --password-stdin "${ECR_URI%%/*}"
docker buildx build \
  --platform linux/amd64 \
  -f "$REPO_ROOT/deploy/docker/Dockerfile" \
  -t "$ECR_URI:$IMAGE_TAG" \
  --push \
  "$REPO_ROOT"

# 5. In-cluster MySQL + Redis + secrets, then wait for MySQL.
echo ">> [4/6] Applying in-cluster MySQL/Redis + secrets..."
kubectl apply -f "$SCRIPT_DIR/in-cluster-deps.yaml"
kubectl -n "$NAMESPACE" rollout status deploy/mysql --timeout=180s
kubectl -n "$NAMESPACE" rollout status deploy/redis --timeout=120s

# 6. Deploy the app (the chart's pre-install hook runs the migration).
echo ">> [5/6] Helm install..."
helm upgrade --install "$PROJECT" "$REPO_ROOT/deploy/helm/phpenterpriseblog" \
  --namespace "$NAMESPACE" \
  --values "$REPO_ROOT/deploy/helm/phpenterpriseblog/values.yaml" \
  --values "$SCRIPT_DIR/values.demo.yaml" \
  --set image.repository="$ECR_URI" \
  --set image.tag="$IMAGE_TAG" \
  --wait --timeout 5m

# 7. Seed demo data so the home page has a post to show.
echo ">> [6/6] Seeding demo data..."
kubectl -n "$NAMESPACE" exec -i deploy/mysql -- \
  mysql -uroot -proot phpenterpriseblog < "$REPO_ROOT/database/seeds/0001_demo.sql"

cat <<EOF

✅ Demo is up.
   View it:   kubectl -n $NAMESPACE port-forward svc/$PROJECT 8080:80
              then open http://localhost:8080  (health: /healthz)
   Tear down: $SCRIPT_DIR/down.sh
EOF
