$AGENTMEMORY_DIR = Join-Path $PSScriptRoot ".agentmemory"
$PROJ = $PWD.Path
$LARAVEL = Join-Path $PSScriptRoot "lrv"

wt -w 0 nt -d $AGENTMEMORY_DIR --title "iii-engine" --tabColor "#44a39b" powershell -NoExit -Command "./run-cmd.cmd"
wt -w 0 nt -d $AGENTMEMORY_DIR --title "agentmemory" --tabColor "#f3cd21" powershell -NoExit -Command "npx -y @agentmemory/agentmemory"
wt -w 0 nt -d $PROJ --title "opencode" --tabColor "#90137b" powershell -NoExit -Command "opencode"
wt -w 0 nt -d $LARAVEL --title "vagrant" --tabColor "#201390" powershell -NoExit -Command "vagrant up"