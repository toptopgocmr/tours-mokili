// Deterministic hue-rotate (degrees) per city name, so destination
// cards sharing the same CoastalScene illustration still look visually
// distinct from one another (see Components/CoastalScene.vue).
export function cityHue(name) {
    if (!name) return 0;
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = (hash << 5) - hash + name.charCodeAt(i);
        hash |= 0;
    }
    return Math.abs(hash) % 360;
}
