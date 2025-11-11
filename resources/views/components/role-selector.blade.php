<!-- resources/views/components/role-selector.blade.php -->
<div class="role-selector-wrapper">
    <style>
        .role-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            align-items: start;
        }

        .role-card {
            background: var(--card);
            color: var(--card-foreground);
            border: 1px solid var(--border);
            padding: 1rem;
            border-radius: 0.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            min-height: 160px;
            transition: transform .12s ease, box-shadow .12s ease;
        }

        .role-card:hover {
            transform: translateY(-4px);
        }

        .role-card[data-role="client"] { --rs-accent: var(--role-client); --rs-accent-hover: var(--role-client-hover); }
        .role-card[data-role="dcd"]    { --rs-accent: var(--role-dcd);    --rs-accent-hover: var(--role-dcd-hover); }
        .role-card[data-role="da"]     { --rs-accent: var(--role-da);     --rs-accent-hover: var(--role-da-hover); }

        .role-title { font-weight: 700; font-size: 1.05rem; }
        .role-desc { color: var(--muted-foreground); font-size: 0.95rem; }

        .role-cta {
            background: var(--rs-accent);
            color: #fff;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            width: max-content;
            transition: background .12s ease, box-shadow .12s ease;
        }

        .role-cta:hover { background: var(--rs-accent-hover); }

        .role-card[selected] {
            outline: 3px solid var(--rs-accent);
            outline-offset: 2px;
            box-shadow: 0 10px 30px rgba(2,6,23,0.12);
        }

        .role-card:focus-within {
            box-shadow: 0 0 0 6px var(--focus-ring);
        }

        /* hide default radios */
        .role-input { position: absolute; left: -9999px; }
    </style>

    <form class="role-selector" aria-label="Choose your role">
        <input type="radio" id="role-client" name="role" value="client" class="role-input" />
        <label for="role-client" class="role-card" data-role="client">
            <div class="flex items-center justify-between">
                <div>
                    <div class="role-title">Client</div>
                    <div class="role-desc">Create your account and launch your first campaign.</div>
                </div>
                <div aria-hidden="true">
                    <!-- simple icon -->
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <rect width="24" height="24" rx="6" fill="var(--rs-accent)"></rect>
                        <path d="M8 12h8M8 16h5" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-auto">
                <button type="button" class="role-cta" onclick="document.getElementById('role-client').checked = true; selectRole('role-client')">Choose</button>
            </div>
        </label>

        <input type="radio" id="role-dcd" name="role" value="dcd" class="role-input" />
        <label for="role-dcd" class="role-card" data-role="dcd">
            <div class="flex items-center justify-between">
                <div>
                    <div class="role-title">Digital Content Distributor</div>
                    <div class="role-desc">Share content at your business and earn rewards.</div>
                </div>
                <div aria-hidden="true">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <rect width="24" height="24" rx="6" fill="var(--rs-accent)"></rect>
                        <path d="M7 12h10M7 8h10" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-auto">
                <button type="button" class="role-cta" onclick="document.getElementById('role-dcd').checked = true; selectRole('role-dcd')">Choose</button>
            </div>
        </label>

        <input type="radio" id="role-da" name="role" value="da" class="role-input" />
        <label for="role-da" class="role-card" data-role="da">
            <div class="flex items-center justify-between">
                <div>
                    <div class="role-title">Digital Ambassador</div>
                    <div class="role-desc">Promote Daya and grow with commission & shares.</div>
                </div>
                <div aria-hidden="true">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <rect width="24" height="24" rx="6" fill="var(--rs-accent)"></rect>
                        <path d="M8 13h8M8 9h8" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-auto">
                <button type="button" class="role-cta" onclick="document.getElementById('role-da').checked = true; selectRole('role-da')">Choose</button>
            </div>
        </label>
    </form>

    <script>
        function selectRole(inputId) {
            // remove selected from all cards
            document.querySelectorAll('.role-card').forEach(c => c.removeAttribute('selected'));
            const label = document.querySelector('label[for="' + inputId + '"]');
            if (label) label.setAttribute('selected', '');
        }

        // initialize selection based on checked radio (useful if server renders checked)
        document.querySelectorAll('.role-input').forEach(input => {
            if (input.checked) selectRole(input.id);
            input.addEventListener('change', () => selectRole(input.id));
        });
    </script>
</div>
