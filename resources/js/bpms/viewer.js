import NavigatedViewer from 'bpmn-js/lib/NavigatedViewer';

const DIAGRAM_INTERCHANGE_PATTERN = /<\s*(?:[\w.-]+:)?BPMNDiagram\b/i;

function hasDiagramInterchange(xml) {
    return DIAGRAM_INTERCHANGE_PATTERN.test(xml);
}

function setStatus(target, message, tone = 'info') {
    if (!target) {
        return;
    }

    target.textContent = message;
    target.dataset.tone = tone;
}

function downloadXml(filename, xml) {
    const blob = new Blob([xml], { type: 'application/xml;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');

    anchor.href = url;
    anchor.download = filename;
    anchor.click();

    setTimeout(() => URL.revokeObjectURL(url), 0);
}

async function mountViewer(root) {
    const canvasElement = root.querySelector('[data-bpmn-canvas]');
    const textarea = root.querySelector('[data-bpmn-xml]');
    const statusElement = root.querySelector('[data-bpmn-status]');

    if (!canvasElement || !textarea) {
        return;
    }

    const viewer = new NavigatedViewer({
        container: canvasElement,
    });

    if (!hasDiagramInterchange(textarea.value)) {
        setStatus(
            statusElement,
            'This BPMN version was saved without diagram layout metadata, so preview is unavailable. Create a new version and save it once from the visual editor to generate a renderable layout.',
            'warning',
        );

        return;
    }

    try {
        const { warnings } = await viewer.importXML(textarea.value);

        viewer.get('canvas').zoom('fit-viewport');

        setStatus(
            statusElement,
            warnings.length
                ? `Preview loaded with ${warnings.length} warning(s).`
                : 'Preview loaded successfully.',
            warnings.length ? 'warning' : 'success',
        );
    } catch (error) {
        console.error(error);
        setStatus(statusElement, 'The BPMN preview could not be rendered.', 'error');
    }

    root.querySelector('[data-bpmn-action="fit"]')?.addEventListener('click', () => {
        viewer.get('canvas').zoom('fit-viewport');
        setStatus(statusElement, 'Preview fitted to the viewport.', 'info');
    });

    root.querySelector('[data-bpmn-action="download"]')?.addEventListener('click', () => {
        downloadXml(root.dataset.downloadName || 'process-definition.bpmn', textarea.value);
        setStatus(statusElement, 'BPMN XML downloaded successfully.', 'success');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bpmn-viewer]').forEach((root) => {
        mountViewer(root);
    });
});
