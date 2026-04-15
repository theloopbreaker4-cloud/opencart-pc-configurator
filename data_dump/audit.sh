#!/bin/bash
cd /mnt/d/Works/gcomp.ge

echo "=== AUDIT: site_upload vs site ==="
ok=0
bad=0

find site_upload -type f -not -name 'deploy.sql' | while read f; do
    rel="${f#site_upload/}"
    site_file="site/public_html/$rel"

    if [ -f "$site_file" ]; then
        if diff -q "$f" "$site_file" > /dev/null 2>&1; then
            echo "OK: $rel"
            ok=$((ok+1))
        else
            echo "MISMATCH: $rel"
            bad=$((bad+1))
        fi
    else
        echo "MISSING in site/: $rel"
        bad=$((bad+1))
    fi
done

echo ""
echo "=== Check if any files exist on server that we'll overwrite ==="
find site_upload -type f -not -name 'deploy.sql' | while read f; do
    rel="${f#site_upload/}"
    # These are all NEW files, none should exist on production
    echo "NEW: $rel"
done
