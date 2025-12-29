const BASE_API_URL = "/api";

/**
 * Calls the local FastAPI backend for structured extraction
 */
async function extractDataFromImage(file) {
    const formData = new FormData();
    formData.append('file', file);

    try {
        const response = await fetch(`${BASE_API_URL}/extract`, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.detail || `Backend error: ${response.statusText}`);
        }

        const result = await response.json();
        return result.data || {};
    } catch (error) {
        if (error.message.includes('Failed to fetch')) {
            throw new Error("Impossible de contacter le serveur backend. Assurez-vous que le serveur FastAPI (port 8000) est lancé.");
        }
        throw error;
    }
}

/**
 * Calls GROQ AI to generate mail content
 */
async function generateMailContent(params) {
    try {
        const response = await fetch(`${BASE_API_URL}/generate-content`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(params)
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.detail || `AI Generation error: ${response.statusText}`);
        }

        return await response.json();
    } catch (error) {
        throw error;
    }
}

/**
 * Calls backend to generate and download PDF
 */
async function downloadMailPDF(mailData) {
    try {
        const response = await fetch(`${BASE_API_URL}/generate-pdf`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(mailData)
        });

        if (!response.ok) {
            throw new Error(`PDF Generation error: ${response.statusText}`);
        }

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `courrier_${Date.now()}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    } catch (error) {
        throw error;
    }
}
