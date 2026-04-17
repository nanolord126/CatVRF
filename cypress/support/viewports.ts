/**
 * Cypress viewport presets — CatVRF 2026
 * Mobile-first: every new component MUST be verified at 375px before merge.
 */

export const VIEWPORTS = {
    mobile:  [375, 667] as [number, number],
    tablet:  [768, 1024] as [number, number],
    desktop: [1280, 800] as [number, number],
} as const

export type ViewportName = keyof typeof VIEWPORTS

/**
 * Helper: set viewport in Cypress test.
 *
 * @example
 * import { setViewport, VIEWPORTS } from '../support/viewports'
 * setViewport('mobile')  // 375×667
 * setViewport('desktop') // 1280×800
 */
export function setViewport(name: ViewportName): void {
    const [width, height] = VIEWPORTS[name]
    cy.viewport(width, height)
}

/**
 * Run a test block across all viewports.
 *
 * @example
 * forAllViewports((name) => {
 *   it(`shows catalog on ${name}`, () => {
 *     cy.visit('/catalog')
 *     cy.get('[data-cy=product-card]').should('be.visible')
 *   })
 * })
 */
export function forAllViewports(callback: (viewportName: ViewportName) => void): void {
    (Object.keys(VIEWPORTS) as ViewportName[]).forEach((name) => {
        describe(`viewport: ${name} (${VIEWPORTS[name][0]}×${VIEWPORTS[name][1]})`, () => {
            beforeEach(() => setViewport(name))
            callback(name)
        })
    })
}
