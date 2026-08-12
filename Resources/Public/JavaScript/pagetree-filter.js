/**
* Reads a filter phrase shared via link into the backend page tree's own
* filter field. Resolved server-side by PageTreeFilterListener.
*/
class PageTreeFilter {
    #shareParam = 'ximaPageTreeFilter';
    #pendingFilter = null;

    constructor() {
        document.addEventListener('DOMContentLoaded', () => this.#initialize());
        if (document.readyState !== 'loading') {
            this.#initialize();
        }
    }

    #initialize() {
        this.#pendingFilter = this.#extractFilter();
        if (null === this.#pendingFilter) {
            return;
        }
        // A capped retry alone can be too short for a fresh deep link (see
        // #extractFilter); typo3-module-loaded retries once the target module
        // actually exists.
        this.#injectWithRetry(60);
        document.addEventListener('typo3-module-loaded', () => this.#injectWithRetry(20));
    }

    // A fresh deep link gets server-redirected through /typo3/main with our
    // param nested in redirectParams; read it there too, before the module
    // router restores the clean URL.
    #extractFilter() {
        const url = new URL(window.location.href);
        const direct = url.searchParams.get(this.#shareParam);
        if (null !== direct) {
            this.#scrub();
            return direct;
        }
        const redirectParams = url.searchParams.get('redirectParams');
        const nested = null !== redirectParams ? new URLSearchParams(redirectParams).get(this.#shareParam) : null;
        if (null !== nested) {
            this.#scrub();
        }
        return nested;
    }

    #scrub() {
        const strip = () => {
            const url = new URL(window.location.href);
            if (null === url.searchParams.get(this.#shareParam)) {
                return;
            }
            url.searchParams.delete(this.#shareParam);
            window.history.replaceState(window.history.state, '', url.toString());
        };
        strip();
        document.addEventListener('typo3-module-loaded', strip);
    }

    #injectWithRetry(attemptsLeft) {
        // Already applied by the other retry chain, or nothing pending.
        if (null === this.#pendingFilter) {
            return;
        }
        const input = document.getElementById('toolbarSearch');
        if (input) {
            input.value = this.#pendingFilter;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            this.#pendingFilter = null;
            return;
        }
        if (attemptsLeft <= 0) {
            return;
        }
        window.setTimeout(() => this.#injectWithRetry(attemptsLeft - 1), 250);
    }
}

export default new PageTreeFilter();
