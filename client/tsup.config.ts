import { defineConfig } from "tsup";

export default defineConfig({
	entry: ["src/index.ts"],
	format: ["esm"],
	target: "es2022",
	dts: true,
	sourcemap: true,
	clean: true,
	treeshake: true,
	external: ["react", "react-dom", "@tbtop/inertia-admin", "lucide-react"],
});
