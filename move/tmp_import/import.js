const xlsx = require('xlsx');
const mysql = require('mysql2/promise');

async function importData() {
    console.log("Starting import process...");
    // Read the Excel file
    const workbook = xlsx.readFile('../Buku1.xlsx');
    const sheetName = workbook.SheetNames[0];
    const worksheet = workbook.Sheets[sheetName];
    // Read as JSON array of objects (using first row as keys)
    const data = xlsx.utils.sheet_to_json(worksheet, { defval: "" });

    // Connect to database
    const conn = await mysql.createConnection({
        host: 'localhost',
        user: 'root',
        password: '',
        database: 'biokpi_db'
    });

    console.log(`Read ${data.length} rows from Excel.`);

    let insertedEmployees = 0;
    let insertedUsers = 0;
    let skipped = 0;

    for (const row of data) {
        if (!row.full_name) continue;

        const fullName = row.full_name.trim();
        const nik = row.nik ? String(row.nik).trim() : '';
        const dept = row.department ? row.department.trim() : '';
        const roleTitle = row.role_title ? row.role_title.trim() : '';
        const position = row.position ? row.position.trim() : '';

        // Check if employee exists
        const [existing] = await conn.execute("SELECT id FROM employees WHERE full_name = ?", [fullName]);
        
        let empId;
        if (existing.length > 0) {
            skipped++;
            empId = existing[0].id;
        } else {
            // Insert new employee
            const [result] = await conn.execute(
                "INSERT INTO employees (full_name, nik, department, role_title, position) VALUES (?, ?, ?, ?, ?)",
                [fullName, nik, dept, roleTitle, position]
            );
            empId = result.insertId;
            insertedEmployees++;
        }

        // Check if this employee already has a user account
        const [existingUser] = await conn.execute("SELECT id FROM users WHERE employee_id = ?", [empId]);
        
        if (existingUser.length === 0) {
            // Determine role dynamically like the PHP script does
            const adminRoles = ['manager', 'manajer', 'spv', 'supervisor', 'direktur', 'head', 'chief', 'admin', 'hr', 'koordinator'];
            let userRole = 'user';
            
            for (const ar of adminRoles) {
                if (roleTitle.toLowerCase().includes(ar)) {
                    userRole = 'admin';
                    break;
                }
            }

            const defaultPass = 'seabiotrop68'; // as established in tracking_api.php

            await conn.execute(
                "INSERT INTO users (employee_id, password, role) VALUES (?, ?, ?)",
                [empId, defaultPass, userRole]
            );
            insertedUsers++;
        }
    }

    console.log("Import Completed:");
    console.log(`- New Employees Inserted: ${insertedEmployees}`);
    console.log(`- New Users Created: ${insertedUsers}`);
    console.log(`- Skipped Employees (Already Existed): ${skipped}`);

    await conn.end();
}

importData().catch(console.error);
