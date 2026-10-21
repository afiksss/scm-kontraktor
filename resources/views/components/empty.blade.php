@props(['cols' => 1, 'message' => 'Belum ada data'])
<tr>
    <td colspan="{{ $cols }}" class="py-14 text-center text-slate-500">
        <i data-lucide="inbox" class="mx-auto mb-3 w-6 h-6 text-slate-400"></i>
        <strong class="block text-navy text-sm">{{ $message }}</strong>
        <span class="text-xs">Coba ubah kata kunci pencarian atau filter.</span>
    </td>
</tr>
