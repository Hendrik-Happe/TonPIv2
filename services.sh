#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$PROJECT_DIR"

usage() {
    echo "Usage: $0 {start|stop|restart|status}"
    echo ""
    echo "Commands:"
    echo "  start   - Start all TonPI services"
    echo "  stop    - Stop all TonPI services"
    echo "  restart - Restart all TonPI services"
    echo "  status  - Show status of all TonPI services"
    exit 1
}

if [[ $# -ne 1 ]]; then
    usage
fi

COMMAND="$1"

case "$COMMAND" in
    start)
        echo "Starting TonPI services..."
        sudo systemctl start rfid-reader.service
        sudo systemctl start gpio-control.service
        echo "Services started."
        ;;
    stop)
        echo "Stopping TonPI services..."
        sudo systemctl stop rfid-reader.service
        sudo systemctl stop gpio-control.service
        echo "Services stopped."
        ;;
    restart)
        echo "Restarting TonPI services..."
        sudo systemctl restart rfid-reader.service
        sudo systemctl restart gpio-control.service
        echo "Services restarted."
        ;;
    status)
        echo "TonPI services status:"
        sudo systemctl status rfid-reader.service --no-pager -l
        echo ""
        sudo systemctl status gpio-control.service --no-pager -l
        ;;
    *)
        usage
        ;;
esac