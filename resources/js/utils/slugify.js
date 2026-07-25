// Turns "Kinshasa" / "Sao Tome" into "kinshasa" / "sao-tome", used to
// build the expected filename for a destination photo (see
// Components/DestinationImage.vue): public/images/destinations/<slug>.jpg
export function slugify(name) {
    return (name ?? '')
        .toString()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
}
