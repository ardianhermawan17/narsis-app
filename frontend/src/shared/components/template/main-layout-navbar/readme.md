## This component focusing on making navbar

## Design UI/UX
1. we will go like "instagram design"
2. For "desktop" screen size, the navbar will be displayed horizontally at the top of the page, it will have a logo on the left side, and navigation links on the right side
3. For "mobile" screen size, the navbar will be displayed as a hamburger menu, when the user clicks on the hamburger icon, the navigation links will be displayed in a vertical list, and the logo will be displayed at the top of the list

### Rules

1. it has 3 UI component, (main-layout-navbar, main-layout-navbar-desktop, main-layout-navbar-mobile)
2. main-layout-navbar is the entry component, it will decide which UI component to render based on the screen size
3. main-layout-navbar-desktop and main-layout-navbar-mobile are pure UI component, they only care about how to display the navbar, they don't have any logic about screen size
4. main-layout-navbar will use a hook (use-main-layout-navbar) to get the screen size and decide which UI component to render, it will also pass the necessary props to the UI component
5. the hook will use a library like react-responsive to get the screen size and return a boolean value indicating whether it's desktop or mobile
6. the UI components will receive the props from main-layout-navbar and render the navbar accordingly, they will not have any logic about screen size or how to get the screen size
7. this way we can keep the logic and UI separate, and make the components more reusable and easier to maintain.

### CSS
1. Prefer using 'tailwindcss' for styling, but if it's not possible, we can use '.module.css' for styling the components
2. Make sure it should have "animation" using "framer motion" when the navbar is shown or hidden, it should have a smooth transition effect


### Testing storybook

1. it has it owns storybook, at least it has (parameters, desktop, mobile) story
2. parameters story will show how to use the component and what props it accepts
3. desktop story will show how the navbar looks like on desktop screen size, it will use the main-layout-navbar component and set the screen size to desktop using the hook
4. mobile story will show how the navbar looks like on mobile screen size, it will use the main-layout-navbar component and set the screen size to mobile using the hook
5. we can use the storybook controls to allow users to change the props and see how it affects the navbar, for example, we can