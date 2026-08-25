<?php

function categoryLabel(string $category): string
{
    $labels = [
        'garbage' => 'Garbage',
        'recycling' => 'Recycling',
        'illegal_dumping' => 'Illegal Dumping',
        'plastic' => 'Plastic Waste',
        'organic' => 'Organic Waste',
        'e_waste' => 'E-Waste',
        'construction' => 'Construction Waste',
        'other' => 'Other'
    ];

    return $labels[$category] ?? ucfirst(str_replace('_', ' ', $category));
}


function statusLabel(string $status): string
{
    $labels = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'resolved' => 'Resolved',
        'rejected' => 'Rejected'
    ];

    return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
}


function statusBadge(string $status): string
{
    $badges = [
        'pending' => 'warning text-dark',
        'in_progress' => 'info',
        'resolved' => 'success',
        'rejected' => 'danger'
    ];

    return $badges[$status] ?? 'secondary';
}


function priorityBadge(string $priority): string
{
    $badges = [
        'low' => 'secondary',
        'medium' => 'info',
        'high' => 'warning text-dark',
        'urgent' => 'danger'
    ];

    return $badges[$priority] ?? 'secondary';
}