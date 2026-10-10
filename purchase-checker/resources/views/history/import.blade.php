@extends('layouts.app')
@section('content')
<div class="card mb-4" style="max-width:640px"><div class="card-body">
    <h5>Import / update purchase history</h5>
    <form id="history-import-form" method="POST" action="{{ route('history.import.run') }}" data-sheets-url="{{ route('history.import.sheets') }}" data-preview-url="{{ route('history.import.preview') }}" enctype="multipart/form-data">
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
        <div id="future-date-warning" class="alert alert-warning d-none" role="alert">
            <strong>This sheet contains future-dated purchases.</strong>
            <ul id="future-date-list" class="mb-2"></ul>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="confirm_future_dates" value="1" id="confirm-future-dates">
                <label class="form-check-label" for="confirm-future-dates">I reviewed these dates and want to import them.</label>
            </div>
        </div>
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
    <tr><th>When</th><th>File</th><th>Sheet</th><th>Items</th><th></th></tr>
    @foreach($batches as $b)
        <tr>
            <td>{{ $b->created_at->format('d.m.Y H:i') }}</td>
            <td>{{ $b->file_name }}</td>
            <td>{{ $b->sheet_name }}</td>
            <td>{{ $b->rows_count }}</td>
            <td class="text-end">
                <form method="POST" action="{{ route('history.import.destroy', $b) }}" onsubmit="return confirm('Delete this import and its purchase-history items? The original file on your computer will not be affected.')">
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
    const futureDateWarning = document.getElementById('future-date-warning');
    const futureDateList = document.getElementById('future-date-list');
    const confirmFutureDates = document.getElementById('confirm-future-dates');
    let ready = false;
    let requiresFutureDateConfirmation = false;

    async function previewSelectedSheet(file) {
        ready = false;
        status.textContent = 'Checking purchase dates...';

        const payload = new FormData();
        payload.append('file', file);
        payload.append('sheet', select.value);
        payload.append('_token', form.querySelector('input[name="_token"]').value);

        const response = await fetch(form.dataset.previewUrl, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: payload,
        });
        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.errors?.file?.[0] || result.message || 'Could not check purchase dates.');
        }

        const futureDates = result.future_dates || [];
        requiresFutureDateConfirmation = futureDates.length > 0;
        confirmFutureDates.checked = false;
        confirmFutureDates.required = requiresFutureDateConfirmation;
        futureDateList.replaceChildren();

        if (requiresFutureDateConfirmation) {
            for (const futureDate of futureDates) {
                const item = document.createElement('li');
                item.textContent = `${futureDate.date}: ${futureDate.count} ${futureDate.count === 1 ? 'item' : 'items'}`;
                futureDateList.append(item);
            }
            futureDateWarning.classList.remove('d-none');
            status.textContent = 'Review the future dates and confirm before importing.';
        } else {
            futureDateWarning.classList.add('d-none');
            status.textContent = `Using worksheet: ${select.value}. No future-dated purchases found.`;
        }
        ready = true;
    }

    fileInput.addEventListener('change', async () => {
        ready = false;
        requiresFutureDateConfirmation = false;
        picker.classList.add('d-none');
        select.disabled = true;
        select.required = false;
        select.replaceChildren(new Option('Select a worksheet', ''));
        confirmFutureDates.checked = false;
        confirmFutureDates.required = false;
        futureDateWarning.classList.add('d-none');
        futureDateList.replaceChildren();

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
                await previewSelectedSheet(file);
                return;
            }

            picker.classList.remove('d-none');
            select.required = true;
            status.textContent = 'Select the worksheet to import.';
        } catch (error) {
            status.textContent = error.message;
        }
    });

    select.addEventListener('change', async () => {
        ready = false;
        if (!select.value) {
            status.textContent = 'Select the worksheet to import.';
            return;
        }
        try {
            await previewSelectedSheet(fileInput.files[0]);
        } catch (error) {
            status.textContent = error.message;
        }
    });

    form.addEventListener('submit', (event) => {
        if (!ready || (requiresFutureDateConfirmation && !confirmFutureDates.checked)) {
            event.preventDefault();
            status.textContent = !ready
                ? 'Select a worksheet before importing.'
                : 'Review and confirm the future dates before importing.';
        }
    });
})();
</script>
@endsection