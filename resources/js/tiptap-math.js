import katex from 'katex';

export function renderKaTeXInElement(containerElement) {
    if (!containerElement) return;

    // Render block math $$...$$
    const blockMathRegex = /\$\$(.*?)\$\$/g;
    containerElement.innerHTML = containerElement.innerHTML.replace(blockMathRegex, (match, formula) => {
        try {
            return `<div class="my-3 flex justify-center overflow-x-auto py-2">${katex.renderToString(formula.trim(), { displayMode: true, throwOnError: false })}</div>`;
        } catch (e) {
            return match;
        }
    });

    // Render inline math $...$
    const inlineMathRegex = /\$(.*?)\$/g;
    containerElement.innerHTML = containerElement.innerHTML.replace(inlineMathRegex, (match, formula) => {
        try {
            return `<span class="inline-block px-1">${katex.renderToString(formula.trim(), { displayMode: false, throwOnError: false })}</span>`;
        } catch (e) {
            return match;
        }
    });
}

export function renderKaTeXToString(formula, displayMode = false) {
    try {
        return katex.renderToString(formula, { displayMode, throwOnError: false });
    } catch (e) {
        return formula;
    }
}
