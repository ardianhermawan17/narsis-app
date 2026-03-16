import React from 'react';
import {ShadcnProvider} from "@shared/providers/shadcn-provider";

/* HOC Component untuk wrapper disini ya, seperti shadcn */

export const LibraryProvider = ({
  children
}: {
  children: React.ReactNode
}) => {
  return (
    <ShadcnProvider>
      {children}
    </ShadcnProvider>
  )
}