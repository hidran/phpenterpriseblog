#!/usr/bin/env bash
# DESTRUCTIVE: remove everything up.sh created. Safe to run repeatedly.
#
# Order matters: uninstall the Helm release FIRST so that anything it created
# outside CloudFormation (e.g. a cloud load balancer, if you switched the
# Service to type LoadBalancer or enabled the ALB ingress) is deleted by
# Kubernetes before we tear the cluster/VPC down — otherwise leaked ENIs block
# the stack deletion. Then delete the stack, which removes EKS, the VPC, ECR
# (EmptyOnDelete clears its images), and the IAM/OIDC resources.
set -euo pipefail

PROJECT="${PROJECT:-phpenterpriseblog}"
STACK="${STACK:-phpenterpriseblog-demo}"
REGION="${AWS_REGION:-$(aws configure get region 2>/dev/null || echo us-east-1)}"
NAMESPACE="phpenterpriseblog-demo"

echo ">> Region: $REGION  Stack: $STACK"

# 1. Uninstall the app (best-effort — cluster may already be gone).
echo ">> [1/3] Uninstalling Helm release + in-cluster deps..."
helm uninstall "$PROJECT" --namespace "$NAMESPACE" --wait --timeout 3m 2>/dev/null || true
kubectl delete namespace "$NAMESPACE" --wait=true --timeout=180s 2>/dev/null || true

# 2. Delete the CloudFormation stack and wait for completion.
echo ">> [2/3] Deleting CloudFormation stack (EKS + VPC + ECR + IAM/OIDC)..."
aws cloudformation delete-stack --region "$REGION" --stack-name "$STACK"

echo ">> [3/3] Waiting for stack deletion (this takes ~15 min for EKS)..."
aws cloudformation wait stack-delete-complete --region "$REGION" --stack-name "$STACK"

echo "✅ Torn down. Verify nothing remains:"
echo "   aws cloudformation describe-stacks --region $REGION --stack-name $STACK  # should error: does not exist"
