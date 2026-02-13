{{-- SARH v2.0 — Arabic Numeral Converter + Smart Mobile Viewport --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const arabicDigits = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    
    function toArabic(str) {
        return String(str).replace(/[0-9]/g, d => arabicDigits[d]);
    }
    
    function convertNode(node) {
        if (node.nodeType === Node.TEXT_NODE) {
            const orig = node.textContent;
            const converted = toArabic(orig);
            if (orig !== converted) {
                node.textContent = converted;
            }
        } else if (node.nodeType === Node.ELEMENT_NODE) {
            // Skip inputs, textareas, scripts, styles, code elements — keep them Western for data entry
            const skip = ['INPUT','TEXTAREA','SCRIPT','STYLE','CODE','PRE','NOSCRIPT'];
            if (skip.includes(node.tagName)) return;
            
            // Convert placeholder and title attributes
            if (node.getAttribute('title')) {
                node.setAttribute('title', toArabic(node.getAttribute('title')));
            }
            
            for (let child of node.childNodes) {
                convertNode(child);
            }
        }
    }
    
    // Initial conversion
    convertNode(document.body);
    
    // Observe DOM changes (Livewire/SPA navigation)
    const observer = new MutationObserver(mutations => {
        for (const m of mutations) {
            for (const node of m.addedNodes) {
                convertNode(node);
            }
            // Also handle text content changes
            if (m.type === 'characterData' && m.target.nodeType === Node.TEXT_NODE) {
                const parent = m.target.parentNode;
                if (parent && !['INPUT','TEXTAREA','SCRIPT','STYLE','CODE','PRE'].includes(parent.tagName)) {
                    const orig = m.target.textContent;
                    const converted = toArabic(orig);
                    if (orig !== converted) {
                        m.target.textContent = converted;
                    }
                }
            }
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        characterData: true,
    });
});
</script>

{{-- Smart mobile viewport: prevent accidental zoom on inputs but allow pinch zoom --}}
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
<style>
    /* Prevent iOS zoom on input focus by ensuring font-size >= 16px */
    input, select, textarea {
        font-size: 16px !important;
    }
    
    /* Smart table zoom on mobile */
    @media (max-width: 768px) {
        .fi-ta-table {
            font-size: 0.85rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .fi-ta-table th,
        .fi-ta-table td {
            white-space: nowrap;
            padding: 0.5rem 0.75rem !important;
        }
        
        /* Horizontal scroll wrapper */
        .fi-ta-content {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scroll-snap-type: x proximity;
        }
        
        /* Badge compact */
        .fi-badge {
            font-size: 0.7rem !important;
            padding: 0.15rem 0.5rem !important;
        }
        
        /* Stats cards single column */
        .fi-wi-stats-overview {
            grid-template-columns: 1fr !important;
            gap: 0.75rem !important;
        }
        
        /* Compact stat cards */
        .fi-wi-stats-overview-stat {
            padding: 0.75rem !important;
        }
        
        /* Page header compact */
        .fi-page-header-heading {
            font-size: 1.1rem !important;
        }
        
        .fi-page-header-subheading {
            font-size: 0.8rem !important;
        }
    }
    
    /* Touch-friendly: bigger tap targets */
    @media (pointer: coarse) {
        .fi-ta-row {
            min-height: 48px;
        }
        
        .fi-btn {
            min-height: 44px !important;
            min-width: 44px !important;
        }
        
        .fi-sidebar-item-button {
            min-height: 44px !important;
        }
    }
</style>
