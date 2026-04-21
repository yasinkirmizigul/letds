<?php

// Example module permissions.
// Return either:
// 1) ['blog.view', 'blog.create', ...] OR
// 2) ['blog.view' => 'Blog View', ...]
return [
    'blog.view'   => 'Blog View',
    'blog.trash' => 'Blog Trash',
    'blog.create' => 'Blog Create',
    'blog.update' => 'Blog Update',
    'blog.delete' => 'Blog Delete',
    'blog.restore' => 'Blog Restore',
    'blog.force_delete' => 'Blog Force Delete',
];
