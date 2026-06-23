import re

def extract_table(sql_file, table_name):
    with open(sql_file, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()
    
    # Try to find DROP TABLE, CREATE TABLE, and INSERT INTO for the table
    drop_pattern = re.compile(r'DROP TABLE IF EXISTS `?' + table_name + r'`?;', re.IGNORECASE)
    create_pattern = re.compile(r'CREATE TABLE `?' + table_name + r'`? \(.*?\).*?;', re.IGNORECASE | re.DOTALL)
    insert_pattern = re.compile(r'INSERT INTO `?' + table_name + r'`? \([^)]+\) VALUES\s*.*?;', re.IGNORECASE | re.DOTALL)
    insert_pattern2 = re.compile(r'INSERT INTO `?' + table_name + r'`? VALUES\s*.*?;', re.IGNORECASE | re.DOTALL)
    
    drop = drop_pattern.findall(content)
    create = create_pattern.findall(content)
    insert1 = insert_pattern.findall(content)
    insert2 = insert_pattern2.findall(content)
    
    inserts = insert1 + insert2
    
    return drop, create, inserts

print('--- db_kdr.sql ---')
for t in ['users', 'employees']:
    d, c, i = extract_table('db_kdr.sql', t)
    print(f'Table: {t}')
    print(f'Drop count: {len(d)}')
    print(f'Create count: {len(c)}')
    print(f'Insert count: {len(i)}')

print('--- biokpi_db.sql ---')
for t in ['users', 'employees']:
    d, c, i = extract_table('biokpi_db.sql', t)
    print(f'Table: {t}')
    print(f'Drop count: {len(d)}')
    print(f'Create count: {len(c)}')
    print(f'Insert count: {len(i)}')
