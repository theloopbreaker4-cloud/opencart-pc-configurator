import json, subprocess

# Read current permissions
result = subprocess.run(['mysql', '-u', 'gcomp', '-pgcomp123', 'gcomp_ge', '-N', '--raw', '-e',
    'SELECT permission FROM oc_user_group WHERE user_group_id = 1;'],
    capture_output=True, text=True)

raw = result.stdout.strip()
perms = json.loads(raw)

# Fix double-escaped slashes
def fix_routes(routes):
    fixed = []
    for r in routes:
        fixed.append(r.replace('\\/', '/').replace('\\\\/', '/'))
    return fixed

perms['access'] = fix_routes(perms['access'])
perms['modify'] = fix_routes(perms['modify'])

# Add configurator routes if missing
cfg_routes = [
    'extension/module/configurator',
    'extension/module/configurator/addComponent',
    'extension/module/configurator/editComponent',
    'extension/module/configurator/deleteComponent',
    'extension/module/configurator/addRule',
    'extension/module/configurator/deleteRule',
    'extension/module/configurator/updateOrderStatus',
    'extension/module/configurator/deleteOrder',
    'extension/module/configurator/changeStatus',
    'extension/module/configurator/deleteOrderAction',
]

for route in cfg_routes:
    if route not in perms['access']:
        perms['access'].append(route)
    if route not in perms['modify']:
        perms['modify'].append(route)

# Add common routes if missing
for route in ['common/dashboard', 'common/column_left', 'common/profile']:
    if route not in perms['access']:
        perms['access'].append(route)
    if route not in perms['modify']:
        perms['modify'].append(route)

# Write back - use json.dumps which produces clean JSON with forward slashes
new_json = json.dumps(perms, ensure_ascii=False)

# MySQL needs the JSON escaped for SQL string
sql_safe = new_json.replace("'", "\\'")
update_sql = "UPDATE oc_user_group SET permission='" + sql_safe + "' WHERE user_group_id=1;"

subprocess.run(['mysql', '-u', 'gcomp', '-pgcomp123', 'gcomp_ge', '-e', update_sql])

# Verify
result2 = subprocess.run(['mysql', '-u', 'gcomp', '-pgcomp123', 'gcomp_ge', '-N', '--raw', '-e',
    'SELECT permission FROM oc_user_group WHERE user_group_id = 1;'],
    capture_output=True, text=True)
p2 = json.loads(result2.stdout.strip())
has_dashboard = 'common/dashboard' in p2['access']
has_catalog = 'catalog/product' in p2['access']
has_cfg = 'extension/module/configurator' in p2['access']
print(f'Dashboard: {has_dashboard}, Catalog: {has_catalog}, Configurator: {has_cfg}')
print(f'Access: {len(p2["access"])}, Modify: {len(p2["modify"])}')
print('FIXED')
