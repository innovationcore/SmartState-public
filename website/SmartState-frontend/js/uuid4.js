/**
 * Generates a random UUID4 formatted string
 *
 * @returns {string}
 */
function uuid4() {
    //// return uuid of form xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
    var uuid = '', ii;
    // Use crypto.getRandomValues for secure random numbers
    var rnds = new Uint8Array(32);
    if (typeof window !== 'undefined' && window.crypto && window.crypto.getRandomValues) {
        window.crypto.getRandomValues(rnds);
    } else {
        // fallback to insecure Math.random (should not happen in modern browsers)
        for (var j = 0; j < 32; j++) {
           rnds[j] = Math.floor(Math.random() * 256);
        }
    }
    for (ii = 0; ii < 32; ii += 1) {
        switch (ii) {
            case 8:
            case 20:
                uuid += '-';
                uuid += (rnds[ii] % 16).toString(16);
                break;
            case 12:
                uuid += '-';
                uuid += '4';
                break;
            case 16:
                uuid += '-';
                // Set the variant bits: (rnds[ii] % 16 & 0x3 | 0x8)
                uuid += ((rnds[ii] % 16 & 0x3) | 0x8).toString(16);
                break;
            default:
                uuid += (rnds[ii] % 16).toString(16);
        }
    }
    return uuid;
}