import BpmnModeler from 'bpmn-js/lib/Modeler';

const DEFAULT_PROCESS_KEY = 'new-process';
const DEFAULT_PROCESS_NAME = 'New Process';
const DIAGRAM_INTERCHANGE_PATTERN = /<\s*(?:[\w.-]+:)?BPMNDiagram\b/i;

function escapeRegExp(value) {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function escapeXmlAttribute(value) {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('"', '&quot;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll("'", '&apos;');
}

function createStarterDiagram(processKey = DEFAULT_PROCESS_KEY, processName = DEFAULT_PROCESS_NAME) {
    const safeKey = escapeXmlAttribute(processKey);
    const safeName = escapeXmlAttribute(processName);

    return `<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
                  xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI"
                  xmlns:dc="http://www.omg.org/spec/DD/20100524/DC"
                  xmlns:di="http://www.omg.org/spec/DD/20100524/DI"
                  id="Definitions_${safeKey}"
                  targetNamespace="http://bpms.local/bpmn">
  <bpmn:process id="${safeKey}" name="${safeName}" isExecutable="true">
    <bpmn:startEvent id="StartEvent_1" name="Start">
      <bpmn:outgoing>Flow_1</bpmn:outgoing>
    </bpmn:startEvent>
    <bpmn:userTask id="Activity_Review" name="Review task">
      <bpmn:incoming>Flow_1</bpmn:incoming>
      <bpmn:outgoing>Flow_2</bpmn:outgoing>
    </bpmn:userTask>
    <bpmn:endEvent id="EndEvent_1" name="Done">
      <bpmn:incoming>Flow_2</bpmn:incoming>
    </bpmn:endEvent>
    <bpmn:sequenceFlow id="Flow_1" sourceRef="StartEvent_1" targetRef="Activity_Review" />
    <bpmn:sequenceFlow id="Flow_2" sourceRef="Activity_Review" targetRef="EndEvent_1" />
  </bpmn:process>
  <bpmndi:BPMNDiagram id="BPMNDiagram_1">
    <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="${safeKey}">
      <bpmndi:BPMNShape id="StartEvent_1_di" bpmnElement="StartEvent_1">
        <dc:Bounds x="160" y="102" width="36" height="36" />
      </bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="Activity_Review_di" bpmnElement="Activity_Review">
        <dc:Bounds x="270" y="80" width="140" height="80" />
      </bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="EndEvent_1_di" bpmnElement="EndEvent_1">
        <dc:Bounds x="490" y="102" width="36" height="36" />
      </bpmndi:BPMNShape>
      <bpmndi:BPMNEdge id="Flow_1_di" bpmnElement="Flow_1">
        <di:waypoint x="196" y="120" />
        <di:waypoint x="270" y="120" />
      </bpmndi:BPMNEdge>
      <bpmndi:BPMNEdge id="Flow_2_di" bpmnElement="Flow_2">
        <di:waypoint x="410" y="120" />
        <di:waypoint x="490" y="120" />
      </bpmndi:BPMNEdge>
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
</bpmn:definitions>`;
}

function hasDiagramInterchange(xml) {
    return DIAGRAM_INTERCHANGE_PATTERN.test(xml);
}

function hasElementWithId(xml, localName, id) {
    const pattern = new RegExp(
        `<\\s*(?:[\\w.-]+:)?${localName}\\b[^>]*\\bid="${escapeRegExp(id)}"`,
        'i',
    );

    return pattern.test(xml);
}

function looksLikeLegacyStarterDiagram(xml) {
    if (hasDiagramInterchange(xml)) {
        return false;
    }

    return [
        ['startEvent', 'StartEvent_1'],
        ['userTask', 'Activity_Review'],
        ['endEvent', 'EndEvent_1'],
        ['sequenceFlow', 'Flow_1'],
        ['sequenceFlow', 'Flow_2'],
    ].every(([localName, id]) => hasElementWithId(xml, localName, id));
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

function syncSubmitIntent(form, submitter) {
    form.querySelector('[data-submit-intent-fallback]')?.remove();

    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = submitter?.name || 'intent';
    hiddenInput.value = submitter?.value || 'draft';
    hiddenInput.dataset.submitIntentFallback = 'true';

    form.append(hiddenInput);
}

async function mountModeler(root) {
    const textarea = root.querySelector('[data-bpmn-xml]');
    const canvasElement = root.querySelector('[data-bpmn-canvas]');
    const statusElement = root.querySelector('[data-bpmn-status]');
    const fileInput = root.querySelector('[data-bpmn-file]');
    const form = root.closest('form');

    if (!textarea || !canvasElement || !form) {
        return;
    }

    const modeler = new BpmnModeler({
        container: canvasElement,
        keyboard: {
            bindTo: document,
        },
    });

    const processKeyInput = form.querySelector('[name="process_key"]');
    const processNameInput = form.querySelector('[name="name"]');

    root.classList.add('is-enhanced');

    async function importDiagram(xml, options = {}) {
        const { allowLegacyUpgrade = true } = options;
        const source = xml?.trim()
            ? xml
            : createStarterDiagram(
                  processKeyInput?.value?.trim() || DEFAULT_PROCESS_KEY,
                  processNameInput?.value?.trim() || DEFAULT_PROCESS_NAME,
              );
        const upgradedLegacyDiagram = allowLegacyUpgrade && looksLikeLegacyStarterDiagram(source);
        const visualSource = upgradedLegacyDiagram
            ? createStarterDiagram(
                  processKeyInput?.value?.trim() || DEFAULT_PROCESS_KEY,
                  processNameInput?.value?.trim() || DEFAULT_PROCESS_NAME,
              )
            : source;

        try {
            const { warnings } = await modeler.importXML(visualSource);

            textarea.value = visualSource;
            modeler.get('canvas').zoom('fit-viewport');

            if (upgradedLegacyDiagram) {
                setStatus(
                    statusElement,
                    'A legacy starter XML without diagram layout was upgraded for the visual editor. Save this version to persist the canvas layout.',
                    'warning',
                );

                return;
            }

            setStatus(
                statusElement,
                warnings.length
                    ? `Diagram loaded with ${warnings.length} warning(s).`
                    : 'Diagram loaded successfully.',
                warnings.length ? 'warning' : 'success',
            );
        } catch (error) {
            console.error(error);

            if (!hasDiagramInterchange(source)) {
                setStatus(
                    statusElement,
                    'This BPMN XML has no diagram layout metadata, so the visual canvas cannot be drawn yet. Use Load starter diagram, import a BPMN exported from a modeler, or keep editing the raw XML below.',
                    'warning',
                );

                return;
            }

            setStatus(
                statusElement,
                'The BPMN diagram could not be loaded. You can still edit the raw XML below.',
                'error',
            );
        }
    }

    async function syncXml() {
        const { xml } = await modeler.saveXML({ format: true });

        textarea.value = xml;

        return xml;
    }

    root.querySelector('[data-bpmn-action="starter"]')?.addEventListener('click', async () => {
        await importDiagram(
            createStarterDiagram(
                processKeyInput?.value?.trim() || DEFAULT_PROCESS_KEY,
                processNameInput?.value?.trim() || DEFAULT_PROCESS_NAME,
            ),
            { allowLegacyUpgrade: false },
        );
    });

    root.querySelector('[data-bpmn-action="fit"]')?.addEventListener('click', () => {
        modeler.get('canvas').zoom('fit-viewport');
        setStatus(statusElement, 'Viewport fitted to the current diagram.', 'info');
    });

    root.querySelector('[data-bpmn-action="download"]')?.addEventListener('click', async () => {
        try {
            const xml = await syncXml();
            const filename = root.dataset.downloadName || 'process-definition.bpmn';

            downloadXml(filename, xml);
            setStatus(statusElement, 'BPMN XML downloaded successfully.', 'success');
        } catch (error) {
            console.error(error);
            setStatus(statusElement, 'Could not export the BPMN XML.', 'error');
        }
    });

    root.querySelector('[data-bpmn-action="import"]')?.addEventListener('click', () => {
        fileInput?.click();
    });

    fileInput?.addEventListener('change', async (event) => {
        const [file] = event.target.files || [];

        if (!file) {
            return;
        }

        try {
            const xml = await file.text();

            await importDiagram(xml);
            setStatus(statusElement, `Imported ${file.name}.`, 'success');
        } catch (error) {
            console.error(error);
            setStatus(statusElement, 'The selected BPMN file could not be imported.', 'error');
        } finally {
            event.target.value = '';
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        try {
            await syncXml();
            syncSubmitIntent(form, event.submitter);
            form.submit();
        } catch (error) {
            console.error(error);
            setStatus(
                statusElement,
                'Saving the BPMN XML failed. Please review the diagram or use the raw XML field.',
                'error',
            );
        }
    });

    await importDiagram(textarea.value);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bpmn-modeler]').forEach((root) => {
        mountModeler(root);
    });
});
