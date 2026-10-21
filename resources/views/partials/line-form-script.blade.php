@push('scripts')
<script>
    /**
     * Komponen Alpine untuk baris barang dinamis (pembelian, permintaan, mutasi).
     * options  : [{id, name, unit, price?}]
     * initial  : baris awal (dari old input / data yang diedit)
     * withPrice: true bila form memiliki harga satuan
     * stockMap : (opsional) {item_id: {warehouse_id: qty}} untuk menampilkan stok tersedia
     */
    function lineForm(options, initial, withPrice = false, stockMap = {}) {
        const blank = () => ({key: Math.random(), item_id: '', quantity: 1, price: 0});
        return {
            options, withPrice, stockMap, warehouse: '',
            lines: (initial && initial.length ? initial : [blank()]).map(l => ({
                key: Math.random(), item_id: l.item_id ?? '', quantity: l.quantity ?? 1, price: l.price ?? 0,
            })),
            find(l) { return this.options.find(o => o.id == l.item_id); },
            unit(l) { const o = this.find(l); return o ? o.unit : ''; },
            pick(l) { const o = this.find(l); if (o && this.withPrice) l.price = o.price; },
            add() { this.lines.push(blank()); },
            remove(i) { if (this.lines.length > 1) this.lines.splice(i, 1); },
            sub(l) { return (Number(l.quantity) || 0) * (Number(l.price) || 0); },
            total() { return this.lines.reduce((s, l) => s + this.sub(l), 0); },
            rp(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); },
            taken(id, l) { return this.lines.some(x => x !== l && x.item_id == id); },
            available(l) {
                if (!l.item_id || !this.warehouse) return null;
                const m = this.stockMap[l.item_id];
                return m && m[this.warehouse] !== undefined ? Number(m[this.warehouse]) : 0;
            },
            short(l) { const a = this.available(l); return a !== null && Number(l.quantity) > a; },
        };
    }
</script>
@endpush
