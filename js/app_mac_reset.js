
async function resetMac(name) {
    if (!confirm(`Apakah Anda yakin ingin menghapus Security MAC untuk ${name}?\nIni akan memungkinkan user login dari perangkat baru.`)) return;

    try {
        const res = await fetch('php/api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'reset_mac', name: name })
        });
        const data = await res.json();
        if (data.status === 'success') {
            alert(data.message);
            fetchData();
        } else {
            alert("Error: " + data.message);
        }
    } catch (e) {
        alert("Terjadi kesalahan koneksi");
    }
}
