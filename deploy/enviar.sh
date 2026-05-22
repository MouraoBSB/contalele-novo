#!/usr/bin/env bash
# Deploy FTPS do site Conta Lelê.
# Thiago Mourão — https://github.com/MouraoBSB
# Uso: bash deploy/enviar.sh [caminho/relativo/arquivo ...]
#   Sem argumentos: envia todos os arquivos versionados deployáveis.
#   Com argumentos: envia apenas os arquivos indicados.

set -euo pipefail

DIR_PLANO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$DIR_PLANO"
source deploy/credenciais.env

# Arquivos/pastas que NÃO vão para o servidor.
EXCLUIR_REGEX='^(\.git|docs|tests|deploy|Identidade Visual|Instruções Site antigo|config\.exemplo\.php|brand-tokens\.css|tokens\.json|CLAUDE\.md|\.gitignore|(instalar|seed-conteudo)\.php)'

enviar_arquivo() {
    local arquivo="$1"
    local destino="ftp://${FTP_HOST}${FTP_RAIZ}${arquivo}"
    echo "  -> ${arquivo}"
    curl --silent --show-error --ssl-reqd --ftp-create-dirs \
        -T "${arquivo}" "${destino}" \
        --user "${FTP_USUARIO}:${FTP_SENHA}"
}

if [ "$#" -gt 0 ]; then
    LISTA=("$@")
else
    # core.quotepath=false mantém os acentos legíveis para o filtro de exclusão.
    mapfile -t LISTA < <(git -c core.quotepath=false ls-files | grep -Ev "$EXCLUIR_REGEX")
fi

echo "Enviando ${#LISTA[@]} arquivo(s) para ${FTP_HOST}..."
for arquivo in "${LISTA[@]}"; do
    [ -f "$arquivo" ] && enviar_arquivo "$arquivo"
done
echo "Deploy concluído."
