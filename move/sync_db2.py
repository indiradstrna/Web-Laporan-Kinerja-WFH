import re

def sync_tables(source_file, target_file, output_file, tables):
    with open(source_file, 'r', encoding='utf-8', errors='ignore') as f:
        src_content = f.read()
    
    with open(target_file, 'r', encoding='utf-8', errors='ignore') as f:
        tgt_content = f.read()

    for table in tables:
        print(f"Syncing table: {table}")
        
        # 1. Replace CREATE TABLE
        create_pattern = re.compile(r'CREATE TABLE `' + table + r'` \(.*?\).*?;', re.IGNORECASE | re.DOTALL)
        src_create_match = create_pattern.search(src_content)
        
        if src_create_match:
            src_create = src_create_match.group(0)
            tgt_content, count = create_pattern.subn(src_create.replace('\\', '\\\\'), tgt_content, count=1)
            print(f"  CREATE TABLE replaced: {count} time(s)")
        else:
            print("  Warning: CREATE TABLE not found in source")
            
        # 2. Replace INSERT INTO
        # The target file might not have an INSERT INTO if it's empty, or it might have multiple
        # Actually biokpi_db.sql does have INSERT INTO for both
        insert_pattern = re.compile(r'INSERT INTO `' + table + r'` \([^)]+\) VALUES\s*.*?;', re.IGNORECASE | re.DOTALL)
        src_insert_match = insert_pattern.search(src_content)
        
        if src_insert_match:
            src_insert = src_insert_match.group(0)
            # Find it in target
            tgt_insert_match = insert_pattern.search(tgt_content)
            if tgt_insert_match:
                tgt_content, count = insert_pattern.subn(src_insert.replace('\\', '\\\\'), tgt_content, count=1)
                print(f"  INSERT INTO replaced: {count} time(s)")
            else:
                # If target doesn't have it, we insert it after CREATE TABLE
                print("  Warning: INSERT INTO not found in target, appending after CREATE TABLE...")
                tgt_content = tgt_content.replace(src_create, src_create + "\n\n-- Dumping data --\n\n" + src_insert)
        else:
            # Maybe there is INSERT INTO without column names?
            insert_pattern2 = re.compile(r'INSERT INTO `' + table + r'` VALUES\s*.*?;', re.IGNORECASE | re.DOTALL)
            src_insert_match2 = insert_pattern2.search(src_content)
            if src_insert_match2:
                src_insert2 = src_insert_match2.group(0)
                tgt_content, count = insert_pattern2.subn(src_insert2.replace('\\', '\\\\'), tgt_content, count=1)
                print(f"  INSERT INTO (no columns) replaced: {count} time(s)")
            else:
                print("  Warning: INSERT INTO not found in source")

    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(tgt_content)
    
    print(f"Done writing to {output_file}")

if __name__ == "__main__":
    tables_to_sync = ['users', 'employees']
    sync_tables('db_kdr.sql', 'biokpi_db.sql', 'biokpi_db_synced.sql', tables_to_sync)

