<?php

$all = ['admin', 'purchasing', 'warehouse', 'project_manager', 'director'];

return [
    'company' => env('SCM_COMPANY', 'PT Karya Bangun Sejahtera'),

    'roles' => [
        'admin' => 'Admin',
        'purchasing' => 'Purchasing',
        'warehouse' => 'Petugas Gudang',
        'project_manager' => 'Manajer Proyek',
        'director' => 'Pimpinan',
    ],

    // Tugas ringkas tiap role (ditampilkan di halaman Hak Akses & form pengguna).
    'role_info' => [
        'admin' => 'Mengelola sistem (pengguna, data master, hak akses) dan dapat membantu semua proses. Untuk pemisahan tugas yang ketat, cabut izin transaksinya di pengaturan hak akses.',
        'purchasing' => 'Membuat & mengajukan pembelian ke supplier. Boleh menambah supplier dan barang baru langsung dari form pembelian.',
        'warehouse' => 'Menerima barang dari supplier, mengeluarkan barang untuk proyek, mutasi antargudang, dan stok opname. Boleh menambah gudang baru saat penerimaan.',
        'project_manager' => 'Meminta barang untuk proyek dan memantau statusnya. Boleh menambah proyek baru langsung dari form permintaan.',
        'director' => 'Menyetujui / menolak pembelian dan permintaan barang, serta memantau laporan dan log aktivitas.',
    ],

    // Daftar izin: kunci => [label, kelompok]. Dipakai halaman Hak Akses.
    'permission_labels' => [
        'purchase.view' => ['Melihat pembelian & penerimaan', 'Pembelian'],
        'purchase.create' => ['Membuat / mengubah / mengajukan pembelian', 'Pembelian'],
        'purchase.approve' => ['Menyetujui / menolak pembelian', 'Pembelian'],
        'purchase.receive' => ['Memverifikasi penerimaan barang (stok masuk)', 'Pembelian'],
        'quick.supplier' => ['Tambah supplier baru dari form pembelian', 'Tambah data cepat'],
        'quick.item' => ['Tambah barang baru dari form pembelian', 'Tambah data cepat'],
        'quick.warehouse' => ['Tambah gudang baru dari form penerimaan', 'Tambah data cepat'],
        'quick.project' => ['Tambah proyek baru dari form permintaan', 'Tambah data cepat'],
        'inventory.view' => ['Melihat inventaris & kartu stok', 'Inventaris'],
        'inventory.manage' => ['Mutasi antargudang & penyesuaian stok (opname)', 'Inventaris'],
        'usage.view' => ['Melihat permintaan / pemakaian barang', 'Pemakaian'],
        'usage.create' => ['Membuat permintaan barang untuk proyek', 'Pemakaian'],
        'usage.approve' => ['Menyetujui / menolak permintaan barang', 'Pemakaian'],
        'usage.release' => ['Memproses & mengeluarkan barang (stok keluar)', 'Pemakaian'],
        'report.view' => ['Melihat & mengekspor laporan', 'Lainnya'],
        'master.manage' => ['Mengubah / menonaktifkan data master', 'Administrasi'],
        'users.manage' => ['Mengelola pengguna & role', 'Administrasi'],
        'roles.manage' => ['Mengubah pengaturan hak akses', 'Administrasi'],
        'activity.view' => ['Melihat log aktivitas', 'Administrasi'],
    ],

    // Pengaturan BAWAAN hak akses (izin => daftar role). Admin dapat mengubahnya lewat
    // menu "Hak Akses > Ubah Pengaturan" tanpa mengedit kode; perubahan tersimpan di database
    // dan menimpa nilai bawaan ini. Tombol "Kembalikan ke bawaan" memulihkan daftar di bawah.
    'permissions' => [
        'master.manage' => ['admin'],

        'purchase.view' => ['admin', 'purchasing', 'warehouse', 'director'],
        'purchase.create' => ['admin', 'purchasing'],
        'purchase.approve' => ['admin', 'director'],
        'purchase.receive' => ['admin', 'warehouse'],

        // Data master tidak wajib diisi lebih dulu: data baru dibuat langsung dari transaksinya.
        'quick.supplier' => ['admin', 'purchasing'],
        'quick.item' => ['admin', 'purchasing'],
        'quick.warehouse' => ['admin', 'warehouse'],
        'quick.project' => ['admin', 'project_manager'],

        'inventory.view' => $all,
        'inventory.manage' => ['admin', 'warehouse'],

        'usage.view' => $all,
        'usage.create' => ['admin', 'project_manager'],
        'usage.approve' => ['admin', 'director'],
        'usage.release' => ['admin', 'warehouse'],

        'report.view' => $all,
        'users.manage' => ['admin'],
        'roles.manage' => ['admin'],
        'activity.view' => ['admin', 'director'],
    ],

    // Izin yang tidak boleh dicabut dari Admin agar sistem tidak terkunci.
    'locked_for_admin' => ['users.manage', 'roles.manage', 'master.manage'],
];
