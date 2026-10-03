/**
 * Tests matchedFilesForRow() -- the lookup deciding which evidence files a
 * row's "Preview" button opens -- in all four travel views at once.
 *
 * Oct 2026: a row may carry several evidence files (e.g. the invoice/receipt
 * plus a supporting email screenshot). The old lookup took only `[0]`, so
 * every file after the first was unreachable from Preview even though it was
 * stored correctly.
 *
 * The method body is read straight out of the .blade.php files rather than
 * duplicated here, so this fails if someone edits the view and breaks the
 * rules -- which is the whole point. There is no JS test runner in this
 * project (see package.json), so this runs standalone:
 *
 *   node tests/js/matched-files-for-row.test.js
 *
 * Exits non-zero on failure, so it can be wired into CI later.
 */
const fs = require('fs');
const path = require('path');

const VIEWS = ['create', 'add-item', 'create-overseas', 'add-item-overseas'];
const VIEW_DIR = path.join(__dirname, '..', '..', 'resources', 'views', 'reimbursement-travel');

/** Pulls one Vue method's body out of a Blade view by brace-matching. */
function extractMethodBody(src, signature) {
    const at = src.indexOf(signature);
    if (at < 0) {
        throw new Error('method not found: ' + signature);
    }
    const start = src.indexOf('{', at + signature.length - 1);
    let depth = 0;
    for (let i = start; i < src.length; i++) {
        if (src[i] === '{') depth++;
        else if (src[i] === '}') {
            depth--;
            if (depth === 0) return src.slice(start, i + 1);
        }
    }
    throw new Error('unbalanced braces in: ' + signature);
}

/**
 * Builds a callable matchedFilesForRow bound to a fake Vue instance. The edit
 * views pool saved+new files through dayFilePool(), so that helper is stubbed
 * to the same flat list the create views read directly.
 */
function loadLookup(view) {
    const src = fs.readFileSync(path.join(VIEW_DIR, view + '.blade.php'), 'utf8');
    const body = extractMethodBody(src, 'matchedFilesForRow(i, a) {');
    const vm = {
        reimburses: [],
        dayFilePool(entry) {
            return (entry.existingFiles || []).concat(entry.dayFiles || []);
        },
    };
    vm.matchedFilesForRow = new Function(
        'i', 'a', 'return (function()' + body + ').call(this)'
    ).bind(vm);
    return vm;
}

const file = (name, rowTag) => ({ name, rowTag });

const CASES = [
    {
        label: 'two files tagged to the same row are BOTH returned',
        files: [file('invoice.jpg', '0'), file('email.png', '0')],
        row: 0,
        expect: ['invoice.jpg', 'email.png'],
    },
    {
        label: 'files tagged to different rows do not leak across rows',
        files: [file('hotel.pdf', '0'), file('taxi.jpg', '1')],
        row: 0,
        expect: ['hotel.pdf'],
    },
    {
        // Oct 2026 bug report: the edit form hands day-level saved files
        // rowTag '' and row-level saved files rowTag '0'. Both chips read
        // "Baris 1" on screen, but the row previewed only one of them because
        // the tagged match short-circuited the untagged one away.
        label: 'a General file is included alongside this row\'s tagged file',
        files: [file('IMG-WA0002.jpg', ''), file('Code_Generated.png', '0')],
        row: 0,
        expect: ['IMG-WA0002.jpg', 'Code_Generated.png'],
    },
    {
        label: 'a General file does NOT drag in another row\'s tagged file',
        files: [file('general.jpg', ''), file('row0.png', '0'), file('row1.png', '1')],
        row: 1,
        expect: ['general.jpg', 'row1.png'],
    },
    {
        label: 'row 1 gets only its own tagged file',
        files: [file('hotel.pdf', '0'), file('taxi.jpg', '1')],
        row: 1,
        expect: ['taxi.jpg'],
    },
    {
        label: 'untagged "General" files apply to every row',
        files: [file('combined.pdf', ''), file('attachment.png', '')],
        row: 0,
        expect: ['combined.pdf', 'attachment.png'],
    },
    {
        // "General (all rows)" covers every row, so it shows together with the
        // row's own file rather than being replaced by it.
        label: 'General and tagged files are shown together, in chip order',
        files: [file('general.pdf', ''), file('specific.jpg', '0')],
        row: 0,
        expect: ['general.pdf', 'specific.jpg'],
    },
    {
        label: 'three files on one row all come back, in upload order',
        files: [file('a.jpg', '2'), file('b.jpg', '2'), file('c.pdf', '2')],
        row: 2,
        expect: ['a.jpg', 'b.jpg', 'c.pdf'],
    },
    {
        label: 'no files at all returns empty (Preview stays disabled)',
        files: [],
        row: 0,
        expect: [],
    },
    {
        // Pre-existing fallback, deliberately preserved: a row with neither its
        // own tagged file nor any General file still shows the last upload
        // rather than nothing.
        label: 'row with no match falls back to the most recent file',
        files: [file('first.jpg', '0'), file('last.jpg', '1')],
        row: 5,
        expect: ['last.jpg'],
    },
];

let failures = 0;
for (const view of VIEWS) {
    let vm;
    try {
        vm = loadLookup(view);
    } catch (e) {
        console.log('FAIL  ' + view + ': ' + e.message);
        failures++;
        continue;
    }

    for (const c of CASES) {
        vm.reimburses = [{ dayFiles: c.files, existingFiles: [] }];
        const got = vm.matchedFilesForRow(0, c.row).map((f) => f.name);
        const ok = JSON.stringify(got) === JSON.stringify(c.expect);
        if (!ok) {
            failures++;
            console.log(
                'FAIL  [' + view + '] ' + c.label +
                '\n        got      [' + got.join(', ') + ']' +
                '\n        expected [' + c.expect.join(', ') + ']'
            );
        }
    }
}

let total = VIEWS.length * CASES.length;

/**
 * The Preview gallery modal: paging must wrap, and must stay safe when the row
 * holds a single file or none. Replaced opening one browser tab per file,
 * which popup blockers dropped inconsistently.
 */
function loadGallery(view) {
    const src = fs.readFileSync(path.join(VIEW_DIR, view + '.blade.php'), 'utf8');
    const vm = { rowPreviewFiles: [], rowPreviewIndex: 0 };
    for (const [name, sig, args] of [
        ['rowPreviewGo', 'rowPreviewGo(step) {', ['step']],
        ['rowPreviewCurrent', 'rowPreviewCurrent() {', []],
        ['rowPreviewSrc', 'rowPreviewSrc(chip) {', ['chip']],
    ]) {
        const body = extractMethodBody(src, sig);
        vm[name] = new Function(
            ...args, 'return (function()' + body + ').call(this)'
        ).bind(vm);
    }
    return vm;
}

const GALLERY_CASES = [
    ['paging forward advances', (vm) => { vm.rowPreviewGo(1); return vm.rowPreviewCurrent().name; }, 'b'],
    ['paging past the end wraps to the first', (vm) => { vm.rowPreviewGo(3); return vm.rowPreviewCurrent().name; }, 'a'],
    ['paging back from the first wraps to the last', (vm) => { vm.rowPreviewGo(-1); return vm.rowPreviewCurrent().name; }, 'c'],
];

for (const view of VIEWS) {
    let vm;
    try {
        vm = loadGallery(view);
    } catch (e) {
        console.log('FAIL  ' + view + ' (gallery): ' + e.message);
        failures++;
        continue;
    }

    for (const [label, act, expect] of GALLERY_CASES) {
        total++;
        vm.rowPreviewFiles = [{ name: 'a' }, { name: 'b' }, { name: 'c' }];
        vm.rowPreviewIndex = 0;
        // A broken index (e.g. paging that no longer wraps) leaves no current
        // file at all, so report that as a plain failure instead of throwing.
        let got;
        try {
            got = act(vm);
        } catch (e) {
            got = '<no current file: ' + e.message + '>';
        }
        if (got !== expect) {
            failures++;
            console.log('FAIL  [' + view + '] ' + label + ' -> got ' + got + ', expected ' + expect);
        }
    }

    // A row with no files must not throw or render a stale file.
    total++;
    vm.rowPreviewFiles = [];
    vm.rowPreviewIndex = 0;
    vm.rowPreviewGo(1);
    if (vm.rowPreviewCurrent() !== null) {
        failures++;
        console.log('FAIL  [' + view + '] empty gallery should expose no current file');
    }

    // Each chip shape must resolve to a displayable URL.
    const srcCases = [
        [{ existingUrl: '/stored.jpg', dataUrl: 'ignored' }, '/stored.jpg'],
        [{ dataUrl: 'data:image/png;base64,x' }, 'data:image/png;base64,x'],
        [{ objectUrl: 'blob:abc' }, 'blob:abc'],
        [null, ''],
    ];
    for (const [chip, expect] of srcCases) {
        total++;
        if (vm.rowPreviewSrc(chip) !== expect) {
            failures++;
            console.log('FAIL  [' + view + '] rowPreviewSrc(' + JSON.stringify(chip) + ')');
        }
    }
}

if (failures) {
    console.log('\n' + failures + ' of ' + total + ' checks FAILED');
    process.exit(1);
}
console.log('OK (' + total + ' checks across ' + VIEWS.length + ' views)');
