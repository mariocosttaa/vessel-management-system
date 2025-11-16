# Coolify Storage Persistence Setup

This guide explains how to preserve the `storage/app` folder between Docker deployments in Coolify.

## Problem

By default, when you deploy a new Docker image, the `storage/app` folder gets overwritten, losing all uploaded files. This is a problem if you're not using cloud storage (S3, etc.) and storing files locally.

## Solution

Use a **Directory Mount** in Coolify to mount `storage/app` from the host, preserving files between deployments.

## Quick Summary

**What you need to do:**
1. Go to **Configuration** → **Persistent Storage** (or **Storages**)
2. Click **"+ Add"** → Select **"Directory Mount"**
3. **Source Directory**: Leave empty (auto-generated) or enter custom path
4. **Destination Directory**: Enter `/var/www/html/storage/app` ⚠️ **This is required!**
5. Click **"Add"** and **Redeploy** your application

**That's it!** Your `storage/app` files will now persist between deployments.

## Step-by-Step Configuration

### Step 1: Navigate to Persistent Storage

1. **Open your application** in Coolify Dashboard
2. Click on the **"Configuration"** tab (or navigate to your application settings)
3. In the left sidebar, click on **"Persistent Storage"** (or look for **"Storages"** section)
4. You should see a page titled **"Storages"** with the message "No storage found" (if this is your first time)

### Step 2: Add Directory Mount

1. Click the **"+ Add"** button (top right of the Storages section)
2. A dropdown menu will appear with three options:
   - Volume Mount
   - File Mount
   - **Directory Mount** ← **Select this one**
3. Click on **"Directory Mount"**

### Step 3: Configure the Directory Mount

A modal dialog will open titled **"Add Directory Mount"**. Fill in the fields:

#### Source Directory (Host Path)
- **What it is**: The path on the Coolify host server where files will be stored
- **What to enter**: 
  - You can leave this empty and let Coolify auto-generate it, OR
  - Enter a custom path like: `/data/coolify/applications/your-app-name/storage-app`
  - Coolify will typically auto-generate something like: `/data/coolify/applications/[app-id]/storage-app`
- **Note**: This is where your files will actually be stored on the server

#### Destination Directory (Container Path) ⚠️ **REQUIRED**
- **What it is**: The path inside the Docker container where Laravel expects the storage
- **What to enter**: `/var/www/html/storage/app`
- **Important**: This must be exactly `/var/www/html/storage/app` to match Laravel's storage path

**Example of what the form should look like:**
```
Source Directory:     /data/coolify/applications/[your-app-id]/storage-app
                      (or leave empty for auto-generation)

Destination Directory: /var/www/html/storage/app
                       ⚠️ This is the critical path - must match exactly!
```

### Step 4: Save the Configuration

1. Click the **"Add"** button in the modal dialog
2. The directory mount will be added to your storage list
3. You should now see the mount listed in the Storages section

### Step 5: Redeploy Your Application

1. **Redeploy** your application for the changes to take effect
   - You can do this by clicking "Redeploy" or triggering a new deployment
2. After deployment, the `storage/app` folder will be mounted from the persistent storage
3. All files uploaded to `storage/app` will now persist between deployments

## How It Works

- **During build**: The Dockerfile creates the directory structure but excludes `storage/app` contents (via `.dockerignore`)
- **During deployment**: The volume mount overrides the empty `storage/app` directory with the persistent volume
- **Between deployments**: Files in the volume persist, so uploaded files are preserved

## Verification

After deployment, verify the volume is working:

1. Upload a file through your application
2. Check that it exists in the persistent volume on the host
3. Redeploy the application
4. Verify the file still exists after redeployment

## Alternative: Full Storage Persistence

If you want to preserve the entire `storage` folder (including logs, cache, etc.), you can mount:

- **Container Path**: `/var/www/html/storage`
- **Host Path**: (auto-generated or custom)

**Note**: This will also preserve logs between deployments, which may or may not be desired.

## Troubleshooting

### Files Not Persisting

1. **Check volume mount**: Verify the volume is correctly configured in Coolify
2. **Check permissions**: Ensure the volume has correct permissions (775 for storage directories)
3. **Check path**: Verify the container path matches `/var/www/html/storage/app`

### Permission Issues

If you encounter permission errors, the Dockerfile sets the correct permissions during build. If issues persist:

1. Check that the volume mount preserves permissions
2. You may need to adjust permissions on the host volume directory

## Files Modified

- **`.dockerignore`**: Excludes `storage/app` contents from Docker build
- **`Dockerfile`**: Ensures directory structure exists for volume mounting

## Benefits

✅ **Files persist** between deployments  
✅ **No data loss** when redeploying  
✅ **Works with local storage** (no S3/bucket needed)  
✅ **Automatic** - once configured, works for all future deployments  

