import FingerprintJS from '@fingerprintjs/fingerprintjs';

window.getFingerprint = async function () {
    const fp = await FingerprintJS.load();
    const result = await fp.get();

    return result.visitorId;
};
