import sqlite3
import argparse
import os

def export_to_sqlite_dump(db_path, output_sql_path):
    if not os.path.exists(db_path):
        print(f"Database file '{db_path}' does not exist.")
        return
    conn = sqlite3.connect(db_path)
    with open(output_sql_path, 'w') as f:
        for line in conn.iterdump():
            f.write(f'{line}\n')
    conn.close()
    print(f"Exported '{db_path}' to '{output_sql_path}'")

def import_sql_to_sqlite(db_path, input_sql_path):
    if not os.path.exists(input_sql_path):
        print(f"SQL file '{input_sql_path}' does not exist.")
        return
    conn = sqlite3.connect(db_path)
    cursor = conn.cursor()
    with open(input_sql_path, 'r') as f:
        sql_script = f.read()
    try:
        cursor.executescript(sql_script)
        conn.commit()
        print(f"Imported '{input_sql_path}' into '{db_path}'")
    except sqlite3.Error as e:
        print("Error executing SQL script:", e)
    finally:
        conn.close()

def main():
    parser = argparse.ArgumentParser(description="Import/export SQLite database to/from SQL file.")
    subparsers = parser.add_subparsers(dest='command', help='Command to run')

    # Export
    export_parser = subparsers.add_parser('export', help='Export SQLite DB to SQL file')
    export_parser.add_argument('--db', required=True, help='Path to .sqlite database file')
    export_parser.add_argument('--out', required=True, help='Output .sql file path')

    # Import
    import_parser = subparsers.add_parser('import', help='Import SQL file to SQLite DB')
    import_parser.add_argument('--db', required=True, help='Path to .sqlite database file')
    import_parser.add_argument('--in', required=True, dest='infile', help='Input .sql file path')

    args = parser.parse_args()

    if args.command == 'export':
        export_to_sqlite_dump(args.db, args.out)
    elif args.command == 'import':
        import_sql_to_sqlite(args.db, args.infile)
    else:
        parser.print_help()

if __name__ == "__main__":
    main()
