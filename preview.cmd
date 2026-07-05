@echo off
REM ==== GreenArc local preview (double-click this file) ====
REM Starts a small local web server and opens the site in your browser.
REM This file is for local viewing only - do NOT upload it to your host.
echo Starting GreenArc preview at http://localhost:8123 ...
start "" http://localhost:8123
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0.dev-server.ps1" -Port 8123 -Root "%~dp0."
