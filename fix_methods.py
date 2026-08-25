import os

path = 'routes/web.php'
with open(path, 'r', encoding='utf-8') as f:
    c = f.read()

c = c.replace("Route::post('/update-stok/update/{id}'", "Route::put('/update-stok/update/{id}'")
c = c.replace("Route::post('/update-stok/hapus/{id}'", "Route::delete('/update-stok/hapus/{id}'")
c = c.replace("Route::post('/riwayat/daily-clean/hapus/{id}'", "Route::delete('/riwayat/daily-clean/hapus/{id}'")

with open(path, 'w', encoding='utf-8') as f:
    f.write(c)
