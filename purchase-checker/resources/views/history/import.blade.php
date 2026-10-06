@extends('layouts.app')
@section('content')
<div class="card mb-4" style="max-width:640px"><div class="card-body">
    <h5>Import / update purchase history</h5>
    <form id="history-import-form" method="POST" action="{{ route('history.import.run') }}" data-sheets-url="{{ route('history.import.sheets') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3"><label class="form-label">Excel file</label>
            <input id="history-import-file" type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required></div>
        <div id="worksheet-picker" class="mb-3 d-none">
            <label class="form-label" for="worksheet-name">Worksheet</label>
            <select id="worksheet-name" name="sheet" class="form-select" disabled>
                <option value="">Select a worksheet</option>
            </select>
        </div>
        <div id="worksheet-status" class="form-text mb-3" role="status" aria-live="polite">Choose a file to detect its worksheets.</div>
        <div class="mb-3">
            <div class="form-check"><input class="form-check-input" type="radio" name="mode" value="append" id="m1" checked>
                <label class="form-check-label" for="m1">Add purchases and refresh matching dates from this file and worksheet</label></div>
            <div class="form-check"><input class="form-check-input" type="radio" name="mode" value="replace" id="m2">
                <label class="form-check-label" for="m2">Replace all history with this file</label></div>
        </div>
        <button class="btn btn-primary">Import</button>
    </form>
</div></div>

<h6>Recent imports</h6>
<table class="table table-sm bg-white" style="max-width:640px">
    <tr><th>When</th><th>File</th><th>Sheet</th><th>Rows</th><th></th></tr>
    @foreach($batches as $b)
        <tr>
            <td>{{ $b->created_at->format('d.m.Y H:i') }}</td>
            <td>{{ $b->file_name }}</td>
            <td>{{ $b->sheet_name }}</td>
            <td>{{ $b->rows_count }}</td>
            <td class="text-end">
                <form method="POST" action="{{ route('history.import.destroy', $b) }}" onsubmit="return confirm('Delete this import and its purchase-history rows? The original file on your computer will not be affected.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
                </form>
            </td>
        </tr>
    @endforeach
</table>
<script>
(() => {
    const form = document.getElementById('history-import-form');
    const fileInput = document.getElementById('history-import-file');
    const picker = document.getElementById('worksheet-picker');
    const select = document.getElementById('worksheet-name');
    const status = document.getElementById('worksheet-status');
    let ready = false;

    fileInput.addEventListener('change', async () => {
        ready = false;
        picker.classList.add('d-none');
        select.disabled = true;
        select.required = false;
        select.replaceChildren(new Option('Select a worksheet', ''));

        const file = fileInput.files[0];
        if (!file) {
            status.textContent = 'Choose a file to detect its worksheets.';
            return;
        }

        status.textContent = 'Detecting worksheets...';
        try {
            const payload = new FormData();
            payload.append('file', file);
            payload.append('_token', form.querySelector('input[name="_token"]').value);

            const response = await fetch(form.dataset.sheetsUrl, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: payload,
            });
            const responseBody = await response.text();
            let result;
            try {
                result = JSON.parse(responseBody);
            } catch {
                const serverMessage = responseBody.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                throw new Error(serverMessage
                    ? `Upload failed (HTTP ${response.status}): ${serverMessage.slice(0, 240)}`
                    : `Upload failed with HTTP ${response.status}. Check the Laravel/PHP error log for details.`);
            }

            if (!response.ok) {
                throw new Error(result.errors?.file?.[0] || result.message || 'Could not detect worksheets.');
            }

            const sheets = result.sheets || [];
            if (sheets.length === 0) {
                throw new Error('No worksheets were found in this file.');
            }

            for (const sheet of sheets) {
                select.add(new Option(sheet, sheet));
            }
            select.disabled = false;

            if (sheets.length === 1) {
                select.value = sheets[0];
                ready = true;
                status.textContent = `Using worksheet: ${sheets[0]}`;
                return;
            }

            picker.classList.remove('d-none');
            select.required = true;
            status.textContent = 'Select the worksheet to import.';
        } catch (error) {
            status.textContent = error.message;
        }
    });

    select.addEventListener('change', () => {
        ready = select.value !== '';
    });

    form.addEventListener('submit', (event) => {
        if (!ready) {
            event.preventDefault();
            status.textContent = 'Select a worksheet before importing.';
        }
    });
})();
</script>
@endsection