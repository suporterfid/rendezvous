# Root Rendezvous CLI Wrapper for Windows PowerShell
param (
    [Parameter(Position=0)]
    [string]$Command = "help",
    [Parameter(ValueFromRemainingArguments=$true)]
    [string[]]$Args
)

& "$PSScriptRoot\scripts\rv.ps1" $Command @Args
