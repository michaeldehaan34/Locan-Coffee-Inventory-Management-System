import os

path = r'app\Http\Controllers\HeadbarController.php'
with open(path, 'r', encoding='utf-8') as f:
    c = f.read()

c = c.replace(
    "asset('storage/' . ltrim($p->filename ?? '', '/'))",
    "\\Illuminate\\Support\\Facades\\Storage::url($p->filename)"
)

with open(path, 'w', encoding='utf-8') as f:
    f.write(c)
