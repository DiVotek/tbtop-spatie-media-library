import {
	closestCenter,
	DndContext,
	type DragEndEvent,
	KeyboardSensor,
	PointerSensor,
	useSensor,
	useSensors,
} from "@dnd-kit/core";
import { restrictToParentElement } from "@dnd-kit/modifiers";
import {
	arrayMove,
	rectSortingStrategy,
	SortableContext,
	sortableKeyboardCoordinates,
	useSortable,
} from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import type { CSSProperties, HTMLAttributes, ReactNode } from "react";

/** What a tile spreads to become draggable: the wrapper takes ref + style, the tile button the listeners. */
export interface TileDrag {
	setNodeRef: (node: HTMLElement | null) => void;
	style: CSSProperties;
	buttonProps: HTMLAttributes<HTMLButtonElement>;
}

// Space lifts and drops; Enter stays the tile button's own activation (Replace).
const KEYBOARD_CODES = { start: ["Space"], cancel: ["Escape"], end: ["Space"] };

export function SortableTiles({
	ids,
	onReorder,
	children,
}: {
	ids: string[];
	onReorder: (next: string[]) => void;
	children: ReactNode;
}) {
	// Same thresholds as core's media picker: a plain click stays a click.
	const sensors = useSensors(
		useSensor(PointerSensor, { activationConstraint: { distance: 8 } }),
		useSensor(KeyboardSensor, {
			coordinateGetter: sortableKeyboardCoordinates,
			keyboardCodes: KEYBOARD_CODES,
		}),
	);

	const handleDragEnd = ({ active, over }: DragEndEvent) => {
		if (over === null || active.id === over.id) return;
		const from = ids.indexOf(String(active.id));
		const to = ids.indexOf(String(over.id));
		if (from === -1 || to === -1) return;
		onReorder(arrayMove(ids, from, to));
	};

	return (
		<DndContext
			sensors={sensors}
			collisionDetection={closestCenter}
			onDragEnd={handleDragEnd}
			modifiers={[restrictToParentElement]}
		>
			<SortableContext items={ids} strategy={rectSortingStrategy}>
				{children}
			</SortableContext>
		</DndContext>
	);
}

export function useTileDrag(id: string, disabled: boolean): TileDrag {
	const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
		id,
		disabled,
	});
	return {
		setNodeRef,
		style: {
			transform: CSS.Transform.toString(transform),
			transition,
			opacity: isDragging ? 0.5 : undefined,
			cursor: disabled ? undefined : "grab",
		},
		// dnd-kit's attributes add role/tabIndex meant for a non-button handle;
		// the tile is already a focusable button, so only the description is kept.
		buttonProps: { ...listeners, "aria-describedby": attributes["aria-describedby"] },
	};
}

/** Hooks cannot run inside the tile map, so each sortable tile gets its own component. */
export function SortableTile({
	id,
	disabled,
	children,
}: {
	id: string;
	disabled: boolean;
	children: (drag: TileDrag) => ReactNode;
}) {
	return children(useTileDrag(id, disabled));
}
