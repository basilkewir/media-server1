@extends('layouts.admin')

@section('title', 'SRT Streams Management')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2">📡 SRT Streams Management</h1>
                <a href="{{ route('admin.srt-streams.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Stream
                </a>
            </div>
            <p class="text-muted">Manage SRT stream receivers and RTMP relay configuration</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted text-uppercase font-weight-bold">Total Streams</h6>
                            <h2 class="mb-0" id="total-streams">-</h2>
                        </div>
                        <span class="badge badge-primary" style="font-size: 1.5rem">📊</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted text-uppercase font-weight-bold">Active Streams</h6>
                            <h2 class="mb-0 text-success" id="active-streams">-</h2>
                        </div>
                        <span class="badge badge-success" style="font-size: 1.5rem">✅</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted text-uppercase font-weight-bold">Listening Ports</h6>
                            <h2 class="mb-0 text-info" id="listening-ports">-</h2>
                        </div>
                        <span class="badge badge-info" style="font-size: 1.5rem">🔊</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted text-uppercase font-weight-bold">Inactive</h6>
                            <h2 class="mb-0 text-warning" id="inactive-streams">-</h2>
                        </div>
                        <span class="badge badge-warning" style="font-size: 1.5rem">⏸️</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Streams Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> SRT Stream Receivers
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 25%">Stream Name</th>
                                <th style="width: 15%">SRT Port</th>
                                <th style="width: 15%">RTMP Stream</th>
                                <th style="width: 10%">Status</th>
                                <th style="width: 10%">Bitrate</th>
                                <th style="width: 15%">Last Connected</th>
                                <th style="width: 10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="streams-tbody">
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-spinner fa-spin"></i> Loading streams...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Stream Details Modal -->
    <div class="modal fade" id="streamDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Stream Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="stream-details-content">
                        <i class="fas fa-spinner fa-spin"></i> Loading...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stream Logs Modal -->
    <div class="modal fade" id="streamLogsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Stream Logs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="stream-logs-content" style="max-height: 400px; overflow-y: auto;">
                        <i class="fas fa-spinner fa-spin"></i> Loading logs...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .badge {
        padding: 0.5rem;
    }
    
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .status-active {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-inactive {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .status-pending {
        background-color: #cfe2ff;
        color: #084298;
    }
    
    .status-error {
        background-color: #f8d7da;
        color: #721c24;
    }

    .card {
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .btn-action {
        padding: 0.25rem 0.5rem;
        font-size: 0.85rem;
        margin: 0 2px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        loadStreams();
        loadStatus();
        
        // Refresh every 30 seconds
        setInterval(loadStatus, 30000);
    });

    function loadStreams() {
        fetch('{{ route("admin.srt-streams.list") }}')
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('streams-tbody');
                tbody.innerHTML = '';

                if (data.streams.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No streams found</td></tr>';
                    return;
                }

                data.streams.forEach(stream => {
                    const statusBadge = `<span class="status-badge status-${stream.status}">${stream.status.toUpperCase()}</span>`;
                    const enabledBadge = stream.enabled ? '✅' : '⏸️';

                    tbody.innerHTML += `
                        <tr>
                            <td>
                                <strong>${stream.name}</strong>
                                <br>
                                <small class="text-muted">${stream.stream_id}</small>
                            </td>
                            <td>
                                <code>${stream.srt_port}</code>
                            </td>
                            <td>
                                <code>${stream.rtmp_stream}</code>
                            </td>
                            <td>${statusBadge}</td>
                            <td>${stream.bitrate} kbps</td>
                            <td>
                                <small>${stream.last_connected_at || 'Never'}</small>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info btn-action" onclick="showStreamDetails(${stream.id})" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-secondary btn-action" onclick="showStreamLogs(${stream.id})" title="View Logs">
                                    <i class="fas fa-list"></i>
                                </button>
                                <a href="{{ route('admin.srt-streams.edit', '') }}/${stream.id}" class="btn btn-sm btn-warning btn-action" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-sm btn-danger btn-action" onclick="deleteStream(${stream.id})" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                // Update stats
                document.getElementById('total-streams').textContent = data.total;
                document.getElementById('active-streams').textContent = data.active;
                document.getElementById('inactive-streams').textContent = data.total - data.active;
            });
    }

    function loadStatus() {
        fetch('{{ route("admin.srt-streams.status") }}')
            .then(response => response.json())
            .then(data => {
                const listeningCount = data.streams.filter(s => s.listening).length;
                document.getElementById('listening-ports').textContent = listeningCount;
            });
    }

    function showStreamDetails(streamId) {
        const content = document.getElementById('stream-details-content');
        content.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

        fetch(`{{ route('admin.srt-streams.details', '') }}/${streamId}`)
            .then(response => response.json())
            .then(data => {
                const stream = data.stream;
                content.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Basic Information</h6>
                            <p><strong>Name:</strong> ${stream.name}</p>
                            <p><strong>Stream ID:</strong> <code>${stream.stream_id}</code></p>
                            <p><strong>SRT Port:</strong> <code>${stream.srt_port}</code></p>
                            <p><strong>RTMP Stream:</strong> <code>${stream.rtmp_stream}</code></p>
                            <p><strong>Status:</strong> <span class="badge bg-info">${stream.status}</span></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Configuration</h6>
                            <p><strong>Bitrate:</strong> ${stream.bitrate} kbps</p>
                            <p><strong>Resolution:</strong> ${stream.resolution}</p>
                            <p><strong>Video Codec:</strong> ${stream.codec_video}</p>
                            <p><strong>Audio Codec:</strong> ${stream.codec_audio}</p>
                            <p><strong>Enabled:</strong> ${stream.enabled ? '✅ Yes' : '❌ No'}</p>
                        </div>
                    </div>
                    <hr>
                    <h6>Stream URLs</h6>
                    <p><strong>SRT URL:</strong><br><code>${stream.srt_url}</code></p>
                    <p><strong>RTMP URL:</strong><br><code>${stream.rtmp_url}</code></p>
                    <p><strong>HLS URL:</strong><br><code>${stream.hls_url}</code></p>
                    <p><strong>DASH URL:</strong><br><code>${stream.dash_url}</code></p>
                    <hr>
                    <p><strong>Last Connected:</strong> ${stream.last_connected_at || 'Never'}</p>
                    <p><strong>Created:</strong> ${stream.created_at}</p>
                `;
            });

        new bootstrap.Modal(document.getElementById('streamDetailsModal')).show();
    }

    function showStreamLogs(streamId) {
        const content = document.getElementById('stream-logs-content');
        content.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading logs...';

        fetch(`{{ route('admin.srt-streams.logs', '') }}/${streamId}`)
            .then(response => response.json())
            .then(data => {
                if (data.logs.length === 0) {
                    content.innerHTML = '<p class="text-muted">No logs available</p>';
                    return;
                }

                content.innerHTML = '<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px;">' +
                    data.logs.map(log => escapeHtml(log)).join('\n') +
                    '</pre>';
            });

        new bootstrap.Modal(document.getElementById('streamLogsModal')).show();
    }

    function deleteStream(streamId) {
        if (!confirm('Are you sure you want to delete this stream?')) return;

        const deleteBtn = event.target.closest('button');
        deleteBtn.disabled = true;

        fetch(`{{ route('admin.srt-streams.destroy', '') }}/${streamId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadStreams();
                    alert('Stream deleted successfully');
                } else {
                    alert('Failed to delete stream: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error deleting stream: ' + error);
            })
            .finally(() => {
                deleteBtn.disabled = false;
            });
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
</script>
@endsection
