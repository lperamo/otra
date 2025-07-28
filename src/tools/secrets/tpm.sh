#!/bin/sh
# Secure TPM AES key sealing and unsealing script.
# This script uses TPM2-tools to seal and unseal an AES key.
# It can run in interactive or non-interactive mode.
#
# Usage:
#   ./tpm_decrypt.sh [-b BASE_PATH] [-n]
#   -b BASE_PATH: base directory for files (default is current directory)
#   -n: non-interactive mode

set -e  # Exit on any error

# Default values
NON_INTERACTIVE=0
# default  folder
BASE_DIR="."

# Parse options
while getopts "b:n" opt; do
  case "$opt" in
    b) BASE_DIR="$OPTARG" ;;
    n) NON_INTERACTIVE=1 ;;
    *) echo "Usage: $0 [-b BASE_DIR] [-n]" >&2; exit 1 ;;
  esac
done

# Convert BASE_DIR to an absolute path if needed
BASE_DIR=$(cd "$BASE_DIR" && pwd)

# Function for logging only in interactive mode
log() {
  if [ $NON_INTERACTIVE -eq 0 ]; then
    echo "$@"
  fi
}

# Define file paths using the base directory
PERSISTENT_HANDLE="0x81010002"
SEALED_PUB="$BASE_DIR/seal.pub"
SEALED_PRIV="$BASE_DIR/seal.priv"
SEALED_CTX="$BASE_DIR/seal.ctx"
AES_KEY="$BASE_DIR/aes.key"
UNSEALED_OUTPUT="$BASE_DIR/aes.key.dec"

# Check for required commands
command -v openssl >/dev/null 2>&1 || { echo "openssl is required but not installed. Exiting." >&2; exit 1; }
command -v tpm2_createprimary >/dev/null 2>&1 || { echo "tpm2-tools not found. Exiting." >&2; exit 1; }

# Ensure TPM has a primary key; if not, create one.
if ! tpm2_getcap handles-persistent | grep -q "$PERSISTENT_HANDLE"; then  
  log "Creating a persistent primary RSA key in the TPM..."
  tpm2_createprimary -C o -g sha256 -G rsa -c primary.ctx
  tpm2_evictcontrol -C o -c primary.ctx "$PERSISTENT_HANDLE"
  rm -f primary.ctx
else
  log "Using existing persistent primary key ($PERSISTENT_HANDLE)."
fi

# Check if a sealed AES key already exists.
if [ -f "$SEALED_PUB" ] && [ -f "$SEALED_PRIV" ]; then  
  log "Existing sealed AES key found. Skipping key generation."
else
  # Generate the AES key if it doesn't exist.
  log "Generating a new random 256-bit AES key..."
  openssl rand -out "$AES_KEY" 32

  log "Sealing the AES key using TPM..."
  tpm2_create -C "$PERSISTENT_HANDLE" -g sha256 -L "" -p "" -i "$AES_KEY" -u "$SEALED_PUB" -r "$SEALED_PRIV"

  # Securely delete the plaintext AES key after sealing.
  shred -u "$AES_KEY"
fi

log "Loading the sealed object into the TPM..."

if [ $NON_INTERACTIVE -eq 0 ]; then
  tpm2_load -C "$PERSISTENT_HANDLE" -u "$SEALED_PUB" -r "$SEALED_PRIV" -c "$SEALED_CTX"
else
  tpm2_load -C "$PERSISTENT_HANDLE" -u "$SEALED_PUB" -r "$SEALED_PRIV" -c "$SEALED_CTX" > /dev/null
fi

log "Unsealing the AES key..."
export TPM2TOOLS_DEFAULT_AUTH=""
tpm2_unseal -c "$SEALED_CTX" -o "$UNSEALED_OUTPUT"

# Output the unsealed AES key in hexadecimal format.
log "🔑 Unsealed AES Key:"

if [ $NON_INTERACTIVE -eq 0 ]; then
  cat "$UNSEALED_OUTPUT" | xxd -p
else
  xxd -p "$UNSEALED_OUTPUT" | tr -d '\n'
fi 

# Secure deletion of temporary unsealed key and context files.
shred -u "$UNSEALED_OUTPUT" "$SEALED_CTX"
log "🧹 Cleaning completed. TPM sealing and unsealing process finished successfully."
