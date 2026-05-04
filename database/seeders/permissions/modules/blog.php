<?php

// Example module permissions.
// Return either:
// 1) ['blog.view', 'blog.create', ...] OR
// 2) ['blog.view' => 'Yazıları Görüntüleme', ...]
return [
    'blog.view'   => 'Yazıları Görüntüleme',
    'blog.trash' => 'Yazı Çöp Kutusu',
    'blog.create' => 'Yazı Oluşturma',
    'blog.update' => 'Yazı Güncelleme',
    'blog.delete' => 'Yazı Silme',
    'blog.restore' => 'Yazı Geri Yükleme',
    'blog.force_delete' => 'Yazı Kalıcı Silme',
];
