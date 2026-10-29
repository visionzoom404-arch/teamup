#!/usr/bin/env bash
# نصب‌کننده‌ی می‌جا:  sudo bash install.sh your-domain.com
exec bash "$(dirname "$0")/deploy/setup-server.sh" "$@"
