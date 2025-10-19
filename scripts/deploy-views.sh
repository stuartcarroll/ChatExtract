#!/bin/bash

# Deploy view files to production server
# Usage: ./scripts/deploy-views.sh

set -e

echo "🚀 Deploying view files to production..."
echo ""

SERVER="ploi@usvps.stuc.dev"
REMOTE_PATH="/home/ploi/chat.stuc.dev"

# Deploy chat view
echo "📄 Deploying chats/show.blade.php..."
scp resources/views/chats/show.blade.php $SERVER:$REMOTE_PATH/resources/views/chats/show.blade.php

# Deploy gallery view
echo "📄 Deploying gallery/index.blade.php..."
scp resources/views/gallery/index.blade.php $SERVER:$REMOTE_PATH/resources/views/gallery/index.blade.php

# Deploy search view
echo "📄 Deploying search/index.blade.php..."
scp resources/views/search/index.blade.php $SERVER:$REMOTE_PATH/resources/views/search/index.blade.php

# Deploy TagController
echo "📄 Deploying TagController.php..."
scp app/Http/Controllers/TagController.php $SERVER:$REMOTE_PATH/app/Http/Controllers/TagController.php

echo ""
echo "✅ Deployment complete!"
echo ""
echo "Next steps:"
echo "1. Test tagging in gallery: https://chat.stuc.dev/gallery"
echo "2. Test inline tag creation"
echo "3. Run verification: ./scripts/verify-deployment.sh"
