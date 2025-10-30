# GitHub Secrets Configuration for Automatic Deployment

This document explains how to configure the necessary secrets in GitHub to enable automatic deployment to Kubernetes.

## Required Secrets

### 1. KUBE_CONFIG (Required for Kubernetes Deployment)

This secret contains your Kubernetes cluster configuration file that allows GitHub Actions to connect to your cluster.

#### How to Get Your Kubeconfig

**Option 1: From your local machine**
```bash
# Display your current kubeconfig
cat ~/.kube/config

# Or copy it to clipboard (macOS)
cat ~/.kube/config | pbcopy

# Linux (with xclip)
cat ~/.kube/config | xclip -selection clipboard
```

**Option 2: From your cloud provider**

- **DigitalOcean**: Download from the Kubernetes cluster page
- **AWS EKS**: `aws eks update-kubeconfig --name your-cluster-name`
- **GCP GKE**: `gcloud container clusters get-credentials your-cluster-name`
- **Azure AKS**: `az aks get-credentials --resource-group your-rg --name your-cluster`

#### Security Best Practices

⚠️ **IMPORTANT**: Never commit kubeconfig files to your repository!

1. **Create a dedicated service account** (recommended):
```bash
# Create service account for GitHub Actions
kubectl create serviceaccount github-actions -n default

# Create role with necessary permissions
kubectl create clusterrolebinding github-actions-admin \
  --clusterrole=cluster-admin \
  --serviceaccount=default:github-actions

# Get the service account token
kubectl create token github-actions -n default --duration=87600h
```

2. **Create a minimal kubeconfig** with only necessary permissions

3. **Use short-lived tokens** when possible

4. **Rotate credentials regularly**

### 2. DOCKER_USERNAME & DOCKER_PASSWORD (Already Configured)

These secrets are already configured for Docker Hub authentication:
- `DOCKER_USERNAME`: Your Docker Hub username
- `DOCKER_PASSWORD`: Your Docker Hub password or access token

## Adding Secrets to GitHub

### Via GitHub Web Interface

1. Go to your repository on GitHub
2. Navigate to **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**
4. Add each secret:
   - **Name**: `KUBE_CONFIG`
   - **Value**: Paste your kubeconfig file content
5. Click **Add secret**

### Via GitHub CLI

```bash
# Install GitHub CLI if needed
brew install gh  # macOS
# or
sudo apt install gh  # Linux

# Login
gh auth login

# Add the KUBE_CONFIG secret
gh secret set KUBE_CONFIG < ~/.kube/config

# Verify secrets are set
gh secret list
```

## Verifying the Configuration

After adding the secrets, you can verify the setup:

1. **Test the workflow**:
   ```bash
   # Make a small change and push
   git commit --allow-empty -m "test: trigger deployment"
   git push origin main
   ```

2. **Monitor the GitHub Actions**:
   - Go to **Actions** tab in your repository
   - Watch the "Deploy to Production" workflow
   - Check each step for errors

3. **Check deployment status**:
   ```bash
   # After the workflow completes
   kubectl get pods -l app=tavira
   kubectl get deployment tavira-app
   ```

## Troubleshooting

### Error: "Unable to connect to the server"
- **Cause**: Invalid or expired kubeconfig
- **Solution**: Update the `KUBE_CONFIG` secret with fresh credentials

### Error: "Forbidden: User cannot get resource"
- **Cause**: Insufficient permissions
- **Solution**: Grant necessary permissions to the service account

### Error: "Error from server (NotFound): deployments.apps not found"
- **Cause**: Wrong namespace or deployment name
- **Solution**: Verify `K8S_NAMESPACE` and `DEPLOYMENT_NAME` in deploy.yml

## Environment Variables in Workflow

Current configuration in `.github/workflows/deploy.yml`:

```yaml
env:
  DOCKER_IMAGE_PHP: ingmontoyav/tavira-app
  DOCKER_IMAGE_NUXT: ingmontoyav/tavira-nuxt
  DEPLOYMENT_NAME: tavira-app
  K8S_NAMESPACE: default
```

Modify these if your setup differs.

## Security Recommendations

1. ✅ Use service accounts instead of user credentials
2. ✅ Apply principle of least privilege
3. ✅ Rotate credentials every 90 days
4. ✅ Monitor GitHub Actions logs for suspicious activity
5. ✅ Enable branch protection rules on `main`
6. ✅ Require pull request reviews before merging
7. ✅ Enable two-factor authentication on GitHub

## Next Steps

Once secrets are configured:
1. The deployment will be fully automatic on push to `main`
2. You can still use `./scripts/deploy.sh` for manual deployments
3. Monitor your deployments in the Actions tab
4. Review logs if any step fails

## Support

If you encounter issues:
1. Check the workflow logs in GitHub Actions
2. Verify cluster connectivity: `kubectl cluster-info`
3. Test credentials: `kubectl get pods`
4. Review this documentation for troubleshooting steps
