// Inspired by https://github.com/phuoc-ng/html-dom/blob/master/demo/drag-and-drop-element-in-a-list/index.html

class Draggable
{
   constructor(container, onReordered)
   {
      this.container = container;
      this.onReordered = onReordered;
       
      // The current DOM element being dragged.
      this.draggingElement = null;
   
      // A flag indicating we've begun dragging the element.
      this.isDragging = false;
      
      // A flag indicating that the container has been reordered.
      this.isReordered = false;
      
      // A placeholder DIV to indicate where the dragged element can be dropped.
      this.placeholder;
      
      // The current position of mouse relative to the dragging element
      this.dragX = 0;
      this.dragY = 0;
      
      // References to the mouse handlers assigned by this class.
      this.moveHandler = this.mouseMoveHandler.bind(this);
      this.upHandler = this.mouseUpHandler.bind(this);
   }
   
   setup()
   {
      // Add mousedown listener to all "draggable" elements.
      [].slice.call(this.container.querySelectorAll('.draggable')).forEach(function(item) {
         item.addEventListener('mousedown', this.mouseDownHandler.bind(this));
      }.bind(this));
   }

   // *************************************************************************
   //                            Mouse handlers

   mouseDownHandler(e)
   {
      // Store the dragged element.
      // Note: For table cells, drag the entire row.
      if (e.target.closest("tr"))
      {
         this.draggingElement = e.target.closest("tr");
      }
      else
      {
         this.draggingElement = e.target
      }
   
      // Calculate the mouse position
      const rect = this.draggingElement.getBoundingClientRect();
      this.dragX = (e.pageX - rect.left);
      this.dragY = (e.pageY - rect.top);
   
      // Attach the listeners to the main document.
      document.addEventListener('mousemove', this.moveHandler);
      document.addEventListener('mouseup', this.upHandler);
   }

   mouseMoveHandler(e)
   {
      if (!this.isDragging)
      {
         this.isDragging = true;
         this.addPlaceholder();
      }
   
      // Update the position of the dragged element.
      this.draggingElement.style.position = 'absolute';
      this.draggingElement.style.top = `${e.pageY - this.dragY}px`; 
      this.draggingElement.style.left = `${e.pageX - this.dragX}px`;
   
      // Note the previous and next elements in the container.
      const prevElement = this.draggingElement.previousElementSibling;
      const nextElement = this.placeholder.nextElementSibling;

      // Reorder the elements based on the dragging.     
      if (prevElement && 
          Draggable.isAbove(this.draggingElement, prevElement) &&
          (prevElement.rowIndex != 0))  // Don't reorder the table heading'
      {
         Draggable.swap(this.placeholder, this.draggingElement);
         Draggable.swap(this.placeholder, prevElement);
         
         this.isReordered = true;
      }
      else if (nextElement && 
               Draggable.isAbove(nextElement, this.draggingElement))
      {
         Draggable.swap(nextElement, this.placeholder);
         Draggable.swap(nextElement, this.draggingElement);
         
         this.isReordered = true;
      }         
   }

   mouseUpHandler()
   {
      if (this.isDragging)
      {
         this.removePlaceholder();
      
         // Remove the position styles
         this.draggingElement.style.removeProperty('top');
         this.draggingElement.style.removeProperty('left');
         this.draggingElement.style.removeProperty('position');
      
         this.placeholder = null;
         this.isDragging = false;
      }
      
      this.draggingElement = null;
      this.dragX = null;
      this.dragY = null;
      
      // Remove the document mouse handlers.
      document.removeEventListener('mousemove', this.moveHandler);
      document.removeEventListener('mouseup', this.upHandler);
      
      // Callback to handle reordering.
      if (this.isReordered && this.onReordered)
      {
         this.onReordered();
      }
      this.isReordered = false;
   }
   
   // **************************************************************************
   //                              

   addPlaceholder()
   {
      const draggingRect = this.draggingElement.getBoundingClientRect();
     
      // Create a div element as a placeholder.
      this.placeholder = document.createElement('div');
      this.placeholder.classList.add('placeholder');
      this.draggingElement.parentNode.insertBefore(this.placeholder, this.draggingElement.nextSibling);
   
      // Set the placeholder's height
      this.placeholder.style.height = `${draggingRect.height}px`;
   }
   
   removePlaceholder()
   {
      if (this.placeholder != null)
      {
         this.placeholder.parentNode.removeChild(this.placeholder);
      }
   }
   
   static swap(nodeA, nodeB)
   {
      const parentA = nodeA.parentNode;
      const siblingA = nodeA.nextSibling === nodeB ? nodeA : nodeA.nextSibling;
   
      // Move `nodeA` to before the `nodeB`
      nodeB.parentNode.insertBefore(nodeA, nodeB);
   
      // Move `nodeB` to before the sibling of `nodeA`
      parentA.insertBefore(nodeB, siblingA);
   }
   
   static isAbove(nodeA, nodeB)
   {
      // Get the bounding rectangle of nodes
      const rectA = nodeA.getBoundingClientRect();
      const rectB = nodeB.getBoundingClientRect();
   
      // Compare bounding rectangle horizontal centers.
      return ((rectA.top + (rectA.height / 2)) < (rectB.top + (rectB.height / 2)));
   }
}