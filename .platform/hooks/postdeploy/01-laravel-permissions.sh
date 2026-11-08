#!/bin/bash
set -euo pipefail

# Elastic Beanstalk executes postdeploy hooks from the deployed application root.
APP_USER="$(/opt/elasticbeanstalk/bin/get-config platformconfig -k AppUser 2>/dev/null || echo webapp)"

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R "${APP_USER}:${APP_USER}" storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
